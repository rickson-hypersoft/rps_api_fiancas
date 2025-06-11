<?php

use Illuminate\Http\Request;
use App\Models\Propostal\Propostal;
use App\Http\Controllers\Controller;
use App\Services\Asaas\AsaasClientService;
use App\Models\Propostal\PropostalPayments;
use App\Actions\Asaas\CreateOrUpdateAsaasCustomerAction;

class CheckoutController extends Controller
{
    public function __construct(
        private AsaasClientService $asaasService
    ) {}

    private function initCheckout(Request $request, string $hashLink)
    {
        $propostal = Propostal::where('LINK_HASH', $hashLink)->firstOrFail();

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

    public function criarPagamentoPix(Request $request, $customerId, $hashLink)
    {
        list($customerId, $propostal) = $this->initCheckout($request, $hashLink);

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
                'detalhe_pagamento'   => $detailedResponses,
                'id_pagamento'        => $response['data']['id'],
                'proposta'            => $propostal
            ]);
        }
    }

    public function criarPagamentoBoleto(Request $request, $hashLink)
    {
        list($customerId, $propostal) = $this->initCheckout($request, $hashLink);

        $payload =  [
            'billingType' => 'BOLETO',
            'customer'    => $propostal->ID_USUARIO_INTEGRACAO,
            'value'       => $propostal->PROPOSTA_TOTAL_VALOR,
            'dueDate'     => now()->addDays(3)->toDateString(),
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
                    'METODO_PAGAMENTO'        => 'BOLETO',
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
                'detalhe_pagamento'   => $detailedResponses,
                'id_pagamento'        => $response['data']['id'],
                'proposta'            => $propostal
            ]);
        }
    }

    public function criarPagamentoCartao(Request $request, $customerId, $hashLink) {}

    public function cancelarPagamento($paymentId, $hashLink)
    {
        $paymentStatus = $this->asaasService->getPaymentById($paymentId);
        $propostalPaymentStatus = PropostalPayments::where('');
    }
}
