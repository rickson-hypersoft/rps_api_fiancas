<?php

declare(strict_types = 1);

namespace App\Http\Controllers\PropostalPayments;

use App\Actions\Asaas\CreateOrUpdateAsaasCustomerAction;
use App\Http\Controllers\Controller;
use App\Models\Propostal\Propostal;
use App\Models\Propostal\PropostalPayments;
use App\Services\Asaas\AsaasClientService;
use Illuminate\Http\Request;

class PaymentAsaasController extends Controller
{
    public function checkout(Request $request, string $linkHash)
    {
        $propostal = Propostal::where('LINK_HASH', $linkHash)->firstOrFail();

        $requestSanitize = $this->sanitizeData($request->all(), ['pessoa_cep', 'pessoa_doc', 'numero_cartao']) ?? [];

        // Atualizar o cliente com as informações pessoais dele
        $dataUpdatePropostal = [
            'PESSOA_CEP'          => $requestSanitize['pessoa_cep'],
            'PESSOA_ENDERECO'     => $requestSanitize['pessoa_endereco'],
            'PESSOA_NUMERO'       => $requestSanitize['pessoa_numero'],
            'PESSOA_ESTADO'       => $requestSanitize['pessoa_estado'],
            'PESSOA_CIDADE'       => $requestSanitize['pessoa_cidade'],
            'PESSOA_BAIRRO'       => $requestSanitize['pessoa_bairro'],
            'PESSOA_COMPLEMENTO'  => $requestSanitize['pessoa_complemento'],
            'PROPOSTA_TOTAL_PARC' => $requestSanitize['proposta_total_parc'],
            'PROPOSTA_SETUP_PARC' => $requestSanitize['proposta_setup_parc'],
        ];
        $propostal->update($dataUpdatePropostal);

        $customerId = (new CreateOrUpdateAsaasCustomerAction(
            new AsaasClientService()
        ))->execute($propostal);

        if (! $customerId) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar/atualizar cliente no Asaas.',
            ], 500);
        }

        $asaasService = new AsaasClientService();

        $payloads = $this->buildPayloadPayment($propostal, $requestSanitize);

        $responses         = [];
        $detailedResponses = [];

        foreach ($payloads as $payload) {
            $response     = $asaasService->createPayment($payload);
            $responseData = is_array($response) ? $response : json_decode(json_encode($response), true);
            $responses[]  = $responseData;
        }

        $allConfirmed = true;

        foreach ($responses as $response) {
            if ($response['success'] && isset($response['data']['id'])) {
                $statusPagamento = $response['data']['status'] ?? null;

                $result  = preg_replace('/.*?(\d+)\s+de\s+(\d+).*/', '$1-$2', $response['data']['description']);
                $parcela = explode("-", $result);

                $payment = PropostalPayments::create([
                    'ID_IMOBILIARIA'          => $propostal->ID_IMOBILIARIA,
                    'ID_MOVI'                 => $propostal->ID,
                    'ID_USUARIO_INTEGRACAO'   => $customerId,
                    'ID_PAGAMENTO_INTEGRACAO' => $response['data']['id'] ?? null,
                    'METODO_PAGAMENTO'        => $requestSanitize['metodo_pagamento'],
                    'VALOR'                   => $response['data']['value'] * floatval($parcela[1]),
                    'STATUS'                  => $response['data']['status'] ?? null,
                    'ID_USUARIO'              => $requestSanitize['id_usuario'],
                    'DATA'                    => now()->toDateString(),
                    'HORA'                    => now()->toTimeString(),
                    'DATA_VENCIMENTO'         => $response['data']['dueDate'] ?? null,
                    'DATA_PAGAMENTO'          => $response['data']['clientPaymentDate'] ?? null,
                ]);

                if ($statusPagamento !== 'CONFIRMED') {
                    $allConfirmed = false;
                }

                if ($requestSanitize['metodo_pagamento'] === 'CREDIT_CARD') {
                    $detalhe             = $asaasService->getPaymentById($response['data']['id']); // você precisa implementar isso
                    $detailedResponses[] = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);
                }
            } else {
                $allConfirmed = false;
            }
        }

        if ($allConfirmed) {
            $propostal->update([
                'CONTRATO_STATUS'         => 'Ativo',
                'PROPOSTA_CREDITO_STATUS' => 'Pagamento Efetuado',
            ]);
        }

        if (! empty($detailedResponses)) {
            return response()->json([
                'success'             => true,
                'detalhes_pagamentos' => $detailedResponses,
            ]);
        }

        return response()->json([
            'success'  => true,
            'response' => $responses,
        ]);
    }

    private function buildPayloadPayment($propostal, $request)
    {
        if ($request['metodo_pagamento'] == 'CREDIT_CARD') {
            $valorSetup  = (float) $propostal->PROPOSTA_SETUP_VALOR;
            $valorImovel = (float) $propostal->PROPOSTA_TOTAL_VALOR;

            $parcelasImovel = (int) $propostal->PROPOSTA_TOTAL_PARC;
            $parcelasSetup  = (int) $propostal->PROPOSTA_SETUP_PARC;

            $buildCartaoPayload = function ($valor, $parcelas, $descricao) use ($request, $propostal) {
                $valorParcela = round($valor / $parcelas, 2);

                return [
                    'billingType'      => 'CREDIT_CARD',
                    'description'      => $descricao,
                    'customer'         => $propostal->ID_USUARIO_INTEGRACAO,
                    'value'            => $valor,
                    'dueDate'          => now()->toDateString(),
                    'installmentCount' => $parcelas,
                    'installmentValue' => $valorParcela,
                    'creditCard'       => [
                        'holderName'  => $request['nome_cartao'],
                        'number'      => preg_replace('/\D/', '', $request['numero_cartao']),
                        'expiryMonth' => substr($request['data_vencimento'], 0, 2),
                        'expiryYear'  => '20' . substr($request['data_vencimento'], -2),
                        'ccv'         => $request['cvv'],
                    ],
                    'creditCardHolderInfo' => [
                        'name'          => $propostal->PESSOA_NOME,
                        'email'         => $propostal->PESSOA_EMAIL,
                        'cpfCnpj'       => preg_replace('/\D/', '', $propostal->PESSOA_DOC),
                        'postalCode'    => preg_replace('/\D/', '', $propostal->PESSOA_CEP),
                        'addressNumber' => $propostal->PESSOA_NUMERO,
                        'phone'         => $propostal->PESSOA_TELEFONE,
                        'mobilePhone'   => $propostal->PESSOA_TELEFONE,
                    ],
                ];
            };

            return [
                $buildCartaoPayload($valorSetup, $parcelasSetup, 'Pagamento do Setup'),
                $buildCartaoPayload($valorImovel, $parcelasImovel, 'Pagamento da Taxa do Imóvel'),
            ];
        }

        if ($request['metodo_pagamento'] == 'BOLETO') {
            return [
                // 'billingType'      => $request['metodo_pagamento'],
                // 'customer'         => $customerId,
                // 'value'            => $proposta->PROPOSTA_TOTAL_VALOR,
                // 'dueDate'          => now()->toDateString(),
                // 'installmentCount' => $request['proposta_total_parc'],
                // 'installmentValue' => $request['valor_parcela'],
                // 'creditCard'       => [
                //     'holderName'  => $request['nome_cartao'],
                //     'number'      => preg_replace('/\D/', '', $request->input('numero_cartao')),
                //     'expiryMonth' => substr($request->input('data_vencimento'), 0, 2),
                //     'expiryYear'  => '20' . substr($request->input('data_vencimento'), -2),
                //     'ccv'         => $request->input('cvv'),
                // ],
                // 'creditCardHolderInfo' => [
                //     'name'          => $request->input('pessoa_nome'),
                //     'email'         => 'email@email.com',
                //     'cpfCnpj'       => preg_replace('/\D/', '', $request->input('pessoa_doc')),
                //     'postalCode'    => preg_replace('/\D/', '', $request->input('pessoa_cep')),
                //     'addressNumber' => $request->input('pessoa_numero'),
                //     'phone'         => '34999999999',
                //     'mobilePhone'   => '34999999999',
                // ],
            ];
        }

        if ($request['metodo_pagamento'] == 'PIX') {
            return [
                // 'billingType'      => $request['metodo_pagamento'],
                // 'customer'         => $customerId,
                // 'value'            => $proposta->PROPOSTA_TOTAL_VALOR,
                // 'dueDate'          => now()->toDateString(),
                // 'installmentCount' => $request->input('proposta_total_parc'),
                // 'installmentValue' => $request->input('valor_parcela'),
                // 'creditCard'       => [
                //     'holderName'  => $request->input('nome_cartao'),
                //     'number'      => preg_replace('/\D/', '', $request->input('numero_cartao')),
                //     'expiryMonth' => substr($request->input('data_vencimento'), 0, 2),
                //     'expiryYear'  => '20' . substr($request->input('data_vencimento'), -2),
                //     'ccv'         => $request->input('cvv'),
                // ],
                // 'creditCardHolderInfo' => [
                //     'name'          => $request->input('pessoa_nome'),
                //     'email'         => 'email@email.com',
                //     'cpfCnpj'       => preg_replace('/\D/', '', $request->input('pessoa_doc')),
                //     'postalCode'    => preg_replace('/\D/', '', $request->input('pessoa_cep')),
                //     'addressNumber' => $request->input('pessoa_numero'),
                //     'phone'         => '34999999999',
                //     'mobilePhone'   => '34999999999',
                // ],
            ];
        }
    }
}
