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
    public function __construct(
        private AsaasClientService $asaasService
    ) {}

    private function initCheckout(Request $request, string $linkHash)
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
            'value'       => $propostal->PROPOSTA_TOTAL_VALOR,
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
        // dd($this->initCheckout($request, $linkHash));
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

        if (empty($payloads)) {
            return response()->json(['success' => false, 'message' => 'Dados do payload inválidos'], 400);
        }

        $detalhesPagamentos = [];
        $pagamentoConfirmado = true;

        foreach ($payloads as $index => $payload) {
            // Defina o identificador específico para cada tipo de pagamento
            $descricao = $payload['description'] ?? '';
            if (str_contains($descricao, 'Setup')) {
                $idPagamentoAtual = $idpayment . '_setup';
            } elseif (str_contains($descricao, 'Imóvel')) {
                $idPagamentoAtual = $idpayment; // mantém o original para o pagamento principal
            } else {
                $idPagamentoAtual = $idpayment . "_{$index}";
            }

            // Verifica se existe pagamento com este ID
            $pagamentoExistente = PropostalPayments::where('ID_PAGAMENTO_INTEGRACAO', $idPagamentoAtual)->first();
            if ($pagamentoExistente) {
                $asaasResponse = $this->asaasService->updatePayment($pagamentoExistente->ID_PAGAMENTO_INTEGRACAO, $payload);
                $paymentId = $pagamentoExistente->ID_PAGAMENTO_INTEGRACAO;
            } else {
                $asaasResponse = $this->asaasService->createPayment($payload);
                $paymentId = $asaasResponse['data']['id'] ?? null;
            }

            if (!$paymentId) {
                return response()->json(['success' => false, 'message' => 'Erro ao criar ou atualizar o pagamento'], 500);
            }

            $payWithCardResponse = $this->asaasService->payWithCreditCard($paymentId, $payload);
            $responseData = is_array($payWithCardResponse) ? $payWithCardResponse : json_decode(json_encode($payWithCardResponse), true);

            if (!isset($responseData['success']) || !$responseData['success']) {
                $pagamentoConfirmado = false;
                continue;
            }

            $asaasPaymentId = $responseData['data']['id'];
            $statusPagamento = $responseData['data']['status'] ?? null;

            // Atualiza ou cria o pagamento no banco
            $paymentData = $this->buildInsertPaymentPropostal($propostal, $responseData, $customerId, $request);
            PropostalPayments::updateOrCreate(
                ['ID_PAGAMENTO_INTEGRACAO' => $idPagamentoAtual],
                $paymentData
            );

            // Verifica status do pagamento
            if ($statusPagamento !== 'CONFIRMED') {
                $pagamentoConfirmado = false;
            }

            // Coleta detalhes
            $detalhes = $this->asaasService->getPaymentById($asaasPaymentId);
            $detalhesPagamentos[] = is_array($detalhes) ? $detalhes : json_decode(json_encode($detalhes), true);
        }

        // Atualiza status da proposta
        $propostal->update([
            'CONTRATO_STATUS' => $pagamentoConfirmado ? 'Ativo' : 'Pendente',
            'PROPOSTA_CREDITO_STATUS' => $pagamentoConfirmado ? 'Pagamento Efetuado' : utf8_decode('Pagamento em Análise')
        ]);

        return response()->json([
            'success' => true,
            'detalhes_pagamentos' => $detalhesPagamentos,
        ]);
    }

    public function checkoutBoleto(Request $request, string $linkHash) {}

    public function updatePaymentMethod(Request $request, string $id_payment)
    {
        $payment = PropostalPayments::where('ID_PAGAMENTO_INTEGRACAO', $id_payment)->firstOrFail();

        // Valide se o status permite atualização
        if ($payment->STATUS !== 'PENDING') {
            return response()->json(['success' => false, 'message' => 'Cobrança não pode ser atualizada.'], 400);
        }

        // Novo método de pagamento vindo do front (ex: PIX, BOLETO)
        $novoMetodo = strtoupper($request->input('metodo_pagamento')); // PIX ou BOLETO

        // Atualiza no Asaas
        $payload = [
            'billingType' => $novoMetodo,
            'dueDate'     => now()->toDateString(),
            'value'       => $payment->VALOR,
        ];

        $response = $this->asaasService->updatePayment($id_payment, $payload);

        if (!isset($response['id'])) {
            return response()->json(['success' => false, 'message' => 'Erro ao atualizar cobrança no Asaas.'], 500);
        }

        // Atualiza no banco local
        $payment->update([
            'METODO_PAGAMENTO' => $novoMetodo,
            'DATA_VENCIMENTO'  => $response['dueDate'] ?? now()->toDateString(),
        ]);

        // Se boleto, retorna o link; se pix, pode buscar o QRCode etc.
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

        // Retornar somente o pagamento do imóvel se valor do setup for 0
        if ($valorSetup <= 0) {
            return [
                $buildCartaoPayload($valorImovel, $parcelasImovel, 'Pagamento da Taxa do Imóvel'),
            ];
        }

        // Caso contrário, retorna os dois
        return [
            $buildCartaoPayload($valorSetup, $parcelasSetup, 'Pagamento do Setup'),
            $buildCartaoPayload($valorImovel, $parcelasImovel, 'Pagamento da Taxa do Imóvel'),
        ];
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

    public function getInfoPayment(Request $request, string $idPayment, string $method)
    {
        $requestData = $request->all();
        $asaasService = new AsaasClientService();


        if ($method === 'CREDIT_CARD') {
            $detalhe             = $asaasService->getPaymentById($idPayment);
            $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);
        }

        if ($method === 'PIX') {
            $detalhe             = $asaasService->getQRCodeById($idPayment);
            $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);
        }

        if ($method === 'BOLETO') {
            $detalhe             = $asaasService->getLineBoletoById($idPayment);
            $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);
        }

        if (! empty($detailedResponses)) {
            return response()->json([
                'success'             => true,
                'detalhes_pagamentos' => [$detailedResponses],
            ]);
        }
    }




        */
}