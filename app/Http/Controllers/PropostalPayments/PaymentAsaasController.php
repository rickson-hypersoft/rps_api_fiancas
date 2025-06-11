<?php

declare(strict_types=1);

namespace App\Http\Controllers\PropostalPayments;

use Illuminate\Http\Request;
use App\Models\Propostal\Propostal;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Asaas\AsaasClientService;
use App\Models\Propostal\PropostalPayments;
use App\Actions\Asaas\CreateOrUpdateAsaasCustomerAction;

use function PHPUnit\Framework\matches;

class PaymentAsaasController extends Controller
{
    public function __construct(
        private AsaasClientService $asaasService
    ) {}

    private function initCheckout(Request $request, string $linkHash)
    {
        $propostal = Propostal::where('LINK_HASH', $linkHash)->firstOrFail();

        $requestSanitize = $this->sanitizeData($request->all(), ['pessoa_cep', 'pessoa_doc', 'numero_cartao']) ?? [];

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

        return [$customerId, $propostal];
    }

    public function checkoutBase(Request $request, string $linkHash)
    {
        list($customerId, $propostal) = $this->initCheckout($request, $linkHash);

        $existingPayment = PropostalPayments::where('ID_MOVI', $propostal->ID)
            ->whereIn('STATUS', ['PENDING']) // status que você quiser considerar como "ativos"
            ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => true,
                'id' => $existingPayment->ID_PAGAMENTO_INTEGRACAO,
            ]);
        }

        $payload =  [
            'billingType' => 'UNDEFINED', // Verifique se API aceita esse valor
            'customer'    => $propostal->ID_USUARIO_INTEGRACAO,
            'value'       => $propostal->PROPOSTA_TOTAL_VALOR + $propostal->PROPOSTA_SETUP_VALOR,
            'dueDate'     => now()->toDateString(),
        ];

        $response = $this->asaasService->createPayment($payload);

        if ($response['success'] && isset($response['data']['id'])) {
            $paymentId = $response['data']['id'];

            $payment = PropostalPayments::create([
                'ID_IMOBILIARIA'          => $propostal->ID_IMOBILIARIA,
                'ID_MOVI'                 => $propostal->ID,
                'ID_USUARIO_INTEGRACAO'   => $customerId,
                'ID_PAGAMENTO_INTEGRACAO' => $paymentId,
                'METODO_PAGAMENTO'        => 'INDEFINIDO', // Para indicar que ainda não foi escolhido
                'VALOR'                   => $response['data']['value'],
                'STATUS'                  => $response['data']['status'] ?? null,
                'ID_USUARIO'              => $request->input('id_usuario'),
                'DATA'                    => now()->toDateString(),
                'HORA'                    => now()->toTimeString(),
                'DATA_VENCIMENTO'         => $response['data']['dueDate'] ?? null,
                'DATA_PAGAMENTO'          => $response['data']['clientPaymentDate'] ?? null,
            ]);

            $propostal->update([
                'CONTRATO_STATUS'         => 'Pendente',
                'PROPOSTA_CREDITO_STATUS' => utf8_decode('Pagamento em Análise'),
            ]);

            return response()->json([
                'success' => true,
                'id'      => $paymentId,
            ]);
        }

        // Caso falhe
        return response()->json([
            'success' => false,
            'message' => 'Erro ao criar cobrança',
        ]);
    }

    public function checkoutPix(Request $request, string $linkHash)
    {
        list($customerId, $propostal) = $this->initCheckout($request, $linkHash);

        $payload =  [
            'billingType' => 'PIX',
            'customer'    => $propostal->ID_USUARIO_INTEGRACAO,
            'value'       => $propostal->PROPOSTA_TOTAL_VALOR,
            'dueDate'     => now()->toDateString(),
        ];

        $response = $this->asaasService->createPayment($payload);

        if ($response['success'] && isset($response['data']['id'])) {
            $statusPagamento = $response['data']['status'] ?? null;

            $payment = PropostalPayments::create(
                [
                    'ID_IMOBILIARIA'          => $propostal->ID_IMOBILIARIA,
                    'ID_MOVI'                 => $propostal->ID,
                    'ID_USUARIO_INTEGRACAO'   => $customerId,
                    'ID_PAGAMENTO_INTEGRACAO' => $response['data']['id'] ?? null,
                    'METODO_PAGAMENTO'        => 'PIX',
                    'VALOR'                   => $response['data']['value'],
                    'STATUS'                  => $response['data']['status'] ?? null,
                    'ID_USUARIO'              => $request['id_usuario'],
                    'DATA'                    => now()->toDateString(),
                    'HORA'                    => now()->toTimeString(),
                    'DATA_VENCIMENTO'         => $response['data']['dueDate'] ?? null,
                    'DATA_PAGAMENTO'          => $response['data']['clientPaymentDate'] ?? null,
                ]
            );

            if ($statusPagamento !== 'CONFIRMED') {
                $allConfirmed = false;
            }

            $detalhe             =  $this->asaasService->getQRCodeById($response['data']['id']);
            $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);
        } else {
            $allConfirmed = false;
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
                'detalhes_pagamentos' => $detailedResponses,
                'id'                  => $response['data']['id'],
                'proposta'           => $propostal
            ]);
        }
    }

    public function checkoutCreditCard(Request $request, string $idpayment, string $linkHash)
    {
        list($customerId, $propostal) = $this->initCheckout($request, $linkHash);

        $requestSanitize = $request->all();

        $payloads = $this->buildPayloadPayment($propostal, $requestSanitize) ?? [];
        dd($payloads['imovel']);

        if (empty($payloads)) {
            return response()->json(['success' => false, 'message' => 'Dados do payload inválidos'], 400);
        }

        $returnResponse = [];
        if ($payloads['imovel']) {
            $pagamentoExistente = PropostalPayments::where('ID_PAGAMENTO_INTEGRACAO', $idpayment)->first();
            if ($pagamentoExistente) {
                $asaasResponse = $this->asaasService->updatePayment($idpayment, $payloads['imovel']);
                Log::info("Atualizando informação no AsaaS", [$asaasResponse]);
                $paymentId = $idpayment;

                if (!$paymentId) {
                    return response()->json(['success' => false, 'message' => 'Erro ao criar ou atualizar o pagamento'], 500);
                }

                $payWithCardResponse = $this->asaasService->payWithCreditCard($paymentId, $payloads['imovel']);
                Log::info("Marcando como pago com cartão no AsaaS", [$payWithCardResponse]);
                $responseData = is_array($payWithCardResponse) ? $payWithCardResponse : json_decode(json_encode($payWithCardResponse), true);
                $paymentData = $this->buildInsertPaymentPropostal($propostal, $responseData, $customerId, $request);
                $asaasPaymentId = $responseData['data']['id'] ?? null;
                Log::info("ID do pagamento do AsaaS", [$asaasPaymentId]);
                $returnResponse[] = $this->asaasService->getPaymentById($paymentId);

                $propostalUpdated = PropostalPayments::updateOrCreate(
                    ['ID_PAGAMENTO_INTEGRACAO' => $asaasPaymentId],
                    $paymentData
                );

                Log::info("Tabela de pagamentos atualizada", [$propostalUpdated]);
            }
        }

        return response()->json(
            [
                'success' => true,
                'pagamentos' => $returnResponse
            ]
        );

        /*


        $detalhesPagamentos = [];
        $pagamentoConfirmado = true;

        foreach ($payloads as $index => $payload) {
            $descricao = $payload['description'] ?? '';

            if ($index === 0) {
                $pagamentoExistente = PropostalPayments::where('ID_PAGAMENTO_INTEGRACAO', $idpayment)->first();
            } else {
                $pagamentoExistente = null;
            }

            if ($pagamentoExistente) {
                $asaasResponse = $this->asaasService->updatePayment($idpayment, $payload);
                $paymentId = $idpayment;
            } else {
                $asaasResponse = $this->asaasService->createPayment($payload);
                $paymentId = $asaasResponse['data']['id'] ?? null;

                // Verificação de "Cobrança já confirmada"
                $erroDescricao = $asaasResponse['data']['errors'][0]['description'] ?? '';
                if (stripos($erroDescricao, 'Cobrança já confirmada') !== false) {
                    $pagamentoConfirmado = true;
                    continue;
                }

                $paymentData = $this->buildInsertPaymentPropostal($propostal, $asaasResponse, $customerId, $request);
                PropostalPayments::create(array_merge($paymentData, [
                    'ID_PAGAMENTO_INTEGRACAO' => $paymentId
                ]));

                $detalhes = $this->asaasService->getPaymentById($paymentId);
                $detalhesPagamentos[] = is_array($detalhes) ? $detalhes : json_decode(json_encode($detalhes), true);
            }

            if (!$paymentId) {
                return response()->json(['success' => false, 'message' => 'Erro ao criar ou atualizar o pagamento'], 500);
            }

            $payWithCardResponse = $this->asaasService->payWithCreditCard($paymentId, $payload);

            $responseData = is_array($payWithCardResponse) ? $payWithCardResponse : json_decode(json_encode($payWithCardResponse), true);
            $paymentData = $this->buildInsertPaymentPropostal($propostal, $responseData, $customerId, $request);
            $asaasPaymentId = $responseData['data']['id'] ?? null;

            if ($index === 0) {
                $propostal = PropostalPayments::updateOrCreate(
                    ['ID_PAGAMENTO_INTEGRACAO' => $asaasPaymentId],
                    $paymentData
                );
            }

            if (!isset($responseData['success']) || !$responseData['success']) {
                $pagamentoConfirmado = false;
                continue;
            }

            if (!$asaasPaymentId) {
                continue;
            }

            $detalhes = $this->asaasService->getPaymentById($asaasPaymentId);
            $detalhesPagamentos[] = is_array($detalhes) ? $detalhes : json_decode(json_encode($detalhes), true);
        }

        $propostal->update([
            'CONTRATO_STATUS' => $pagamentoConfirmado ? 'Ativo' : 'Pendente',
            'PROPOSTA_CREDITO_STATUS' => $pagamentoConfirmado ? 'Pagamento Efetuado' : utf8_decode('Pagamento em Análise')
        ]);

        return response()->json([
            'success' => true,
            'detalhes_pagamentos' => $detalhesPagamentos
        ]);
        */
    }

    public function checkoutBoleto(Request $request, string $linkHash) {}

    public function updatePaymentMethod(Request $request, string $id_payment)
    {
        $payment = PropostalPayments::where('ID_PAGAMENTO_INTEGRACAO', $id_payment)->firstOrFail();

        if ($payment->STATUS !== 'PENDING') {
            return response()->json(['success' => false, 'message' => 'Cobrança não pode ser atualizada.'], 400);
        }

        $novoMetodo = strtoupper($request->input('metodo_pagamento'));

        $payload = [
            'billingType' => $novoMetodo,
            'dueDate'     => now()->toDateString(),
            'value'       => $payment->VALOR,
        ];

        $response = $this->asaasService->updatePayment($id_payment, $payload);

        if (!isset($response['id'])) {
            return response()->json(['success' => false, 'message' => 'Erro ao atualizar cobrança no Asaas.'], 500);
        }

        $payment->update([
            'METODO_PAGAMENTO' => $novoMetodo,
            'DATA_VENCIMENTO'  => $response['dueDate'] ?? now()->toDateString(),
        ]);

        if ($novoMetodo === 'BOLETO') {
            $detalhe = $this->asaasService->getLineBoletoById($id_payment);
            return response()->json([
                'success'  => true,
                'tipo'     => 'BOLETO',
                'link'     => $response['bankSlipUrl'] ?? null,
                'detalhes_pagamentos' => $detalhe,
            ]);
        } elseif ($novoMetodo === 'PIX') {
            $detalhe = $this->asaasService->getQRCodeById($id_payment);
            return response()->json([
                'success'             => true,
                'tipo'                => 'PIX',
                'detalhes_pagamentos' => $detalhe,
            ]);
        }

        return response()->json(['success' => true]);
    }

    private function buildPayloadPayment($propostal, $request)
    {
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

        if ($valorSetup <= 0) {
            return [
                'imovel' => $buildCartaoPayload($valorImovel, $parcelasImovel, 'Pagamento da Taxa do Imóvel'),
            ];
        }

        return [
            'imovel' => $buildCartaoPayload($valorImovel, $parcelasImovel, 'Pagamento da Taxa do Imóvel'),
            'setup'  => $buildCartaoPayload($valorSetup, $parcelasSetup, 'Pagamento do Setup'),
        ];
    }

    private function buildInsertPaymentPropostal($propostal, $response, $customerId, $request)
    {
        $valorUnitario = $response['data']['value'];

        $parcelas = 1;
        if (
            preg_match('/(\d+)\s+de\s+(\d+)/', $response['data']['description'], $matches)
        ) {
            $parcelas = (int) $matches[2];
        }

        $valor = $valorUnitario * $parcelas;

        return [
            'ID_IMOBILIARIA'          => $propostal->ID_IMOBILIARIA,
            'ID_MOVI'                 => $propostal->ID,
            'ID_USUARIO_INTEGRACAO'   => $customerId,
            'ID_PAGAMENTO_INTEGRACAO' => $response['data']['id'] ?? null,
            'METODO_PAGAMENTO'        => 'CREDIT_CARD',
            'VALOR'                   => $valor,
            'STATUS'                  => $response['data']['status'] ?? null,
            'ID_USUARIO'              => $request['id_usuario'],
            'DATA'                    => now()->toDateString(),
            'HORA'                    => now()->toTimeString(),
            'DATA_VENCIMENTO'         => $response['data']['dueDate'] ?? null,
            'DATA_PAGAMENTO'          => $response['data']['clientPaymentDate'] ?? null,
        ];
    }

    /*
    public function checkout(Request $request, string $linkHash)
    {
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
                'detalhes_pagamentos' => $detailedResponses,
                'id'                  => $response['data']['id']
            ]);
        }

        return response()->json([
            'success'  => true,
            'response' => $responses,
        ]);
    }
    */


    public function getInfoPayment(string $idPayment)
    {
        $asaasService = new AsaasClientService();

        $detalhe             = $asaasService->getPaymentById($idPayment);
        $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);

        $propostalsPayments = PropostalPayments::where('ID_PAGAMENTO_INTEGRACAO', '=', $idPayment)->first();
        $propostal = Propostal::where('ID', '=', $propostalsPayments->ID_MOVI)->get();

        if (! empty($detailedResponses)) {
            return response()->json([
                'success'             => true,
                'detalhes_pagamentos' => [$detailedResponses],
                'propostas' => $propostal
            ]);
        }
    }
}
