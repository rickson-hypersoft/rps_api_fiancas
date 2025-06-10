<?php

declare(strict_types=1);

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
        if (
            ! empty($requestSanitize['pessoa_cep']) ||
            ! empty($requestSanitize['pessoa_endereco']) ||
            ! empty($requestSanitize['pessoa_numero']) ||
            ! empty($requestSanitize['pessoa_estado']) ||
            ! empty($requestSanitize['pessoa_cidade']) ||
            ! empty($requestSanitize['pessoa_bairro']) ||
            ! empty($requestSanitize['pessoa_complemento']) ||
            ! empty($requestSanitize['proposta_total_parc']) ||
            ! empty($requestSanitize['proposta_setup_parc'])
        ) {
            $dataUpdatePropostal = [
                'PESSOA_CEP'          => $requestSanitize['pessoa_cep'] ?? null,
                'PESSOA_ENDERECO'     => $requestSanitize['pessoa_endereco'] ?? null,
                'PESSOA_NUMERO'       => $requestSanitize['pessoa_numero'] ?? null,
                'PESSOA_ESTADO'       => $requestSanitize['pessoa_estado'] ?? null,
                'PESSOA_CIDADE'       => $requestSanitize['pessoa_cidade'] ?? null,
                'PESSOA_BAIRRO'       => $requestSanitize['pessoa_bairro'] ?? null,
                'PESSOA_COMPLEMENTO'  => $requestSanitize['pessoa_complemento'] ?? null,
                'PROPOSTA_TOTAL_PARC' => $requestSanitize['proposta_total_parc'] ?? null,
                'PROPOSTA_SETUP_PARC' => $requestSanitize['proposta_setup_parc'] ?? null,
            ];
            $propostal->update($dataUpdatePropostal);
        }

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

                $payment = PropostalPayments::create($this->buildInsertPaymentPropostal($propostal, $response, $customerId, $request));

                if ($statusPagamento !== 'CONFIRMED') {
                    $allConfirmed = false;
                }

                if ($requestSanitize['metodo_pagamento'] === 'CREDIT_CARD') {
                    $detalhe             = $asaasService->getPaymentById($response['data']['id']); // você precisa implementar isso
                    $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);
                }

                if ($requestSanitize['metodo_pagamento'] === 'PIX') {
                    $detalhe             = $asaasService->getQRCodeById($response['data']['id']); // você precisa implementar isso
                    $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);
                }

                if ($requestSanitize['metodo_pagamento'] === 'BOLETO') {
                    $detalhe             = $asaasService->getLineBoletoById($response['data']['id']); // você precisa implementar isso
                    $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);
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

        if (! $allConfirmed) {
            $propostal->update([
                'CONTRATO_STATUS'         => 'Pendente',
                'PROPOSTA_CREDITO_STATUS' => utf8_decode('Pagamento em Análise'),
            ]);
        }

        if (! empty($detailedResponses)) {
            return response()->json([
                'success'             => true,
                'detalhes_pagamentos' => $detailedResponses['id'],
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
                [
                    'billingType' => $request['metodo_pagamento'],
                    'customer'    => $propostal->ID_USUARIO_INTEGRACAO,
                    'value'       => $propostal->PROPOSTA_TOTAL_VALOR,
                    'dueDate'     => now()->toDateString(),
                ],
            ];
        }
    }

    private function buildInsertPaymentPropostal($propostal, $response, $customerId, $request)
    {
        $valorUnitario = $response['data']['value'];
        $parcelas      = 1;

        if (
            $request['metodo_pagamento'] === 'CREDIT_CARD' &&
            ! empty($response['data']['description']) &&
            preg_match('/(\d+)\s+de\s+(\d+)/', $response['data']['description'], $matches)
        ) {
            $parcelas = (int) $matches[2];
        }

        $valor = $valorUnitario * $parcelas;

        if ($request['metodo_pagamento'] !== 'CREDIT_CARD') {
            $valor = $valorUnitario;
        }

        return [
            'ID_IMOBILIARIA'          => $propostal->ID_IMOBILIARIA,
            'ID_MOVI'                 => $propostal->ID,
            'ID_USUARIO_INTEGRACAO'   => $customerId,
            'ID_PAGAMENTO_INTEGRACAO' => $response['data']['id'] ?? null,
            'METODO_PAGAMENTO'        => $request['metodo_pagamento'],
            'VALOR'                   => $valor,
            'STATUS'                  => $response['data']['status'] ?? null,
            'ID_USUARIO'              => $request['id_usuario'],
            'DATA'                    => now()->toDateString(),
            'HORA'                    => now()->toTimeString(),
            'DATA_VENCIMENTO'         => $response['data']['dueDate'] ?? null,
            'DATA_PAGAMENTO'          => $response['data']['clientPaymentDate'] ?? null,
        ];
    }
}
