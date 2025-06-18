<?php

declare(strict_types = 1);

namespace App\Http\Controllers\PropostalPayments;

use App\Actions\Asaas\CreateOrUpdateAsaasCustomerAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Propostal\PropostalIndexResource;
use App\Models\Propostal\Propostal;
use App\Models\Propostal\PropostalPayments;
use App\Services\Asaas\AsaasClientService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private AsaasClientService $asaasService
    ) {
    }

    public function recuperarPagamento(string $idPayment)
    {
        $idsArray   = explode(',', $idPayment);
        $pagamentos = [];
        $propostas  = [];

        foreach ($idsArray as $id) {
            $response = $this->asaasService->getPaymentById(trim($id));

            if ($response) {
                $pagamentos[] = $response;
            }

            $propostalsPayments = PropostalPayments::where('ID_PAGAMENTO_INTEGRACAO', '=', $id)->first();
            $propostas          = Propostal::where('ID', '=', $propostalsPayments->ID_MOVI)->get();
        }

        return response()->json([
            'success'             => true,
            'detalhes_pagamentos' => $pagamentos,
            'propostas'           => PropostalIndexResource::collection($propostas),
        ]);
    }

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

    public function criarPagamentoPix(Request $request, $linkHash)
    {
        list($customerId, $propostal) = $this->initCheckout($request, $linkHash);

        $pagamentoExistente = PropostalPayments::where('ID_USUARIO_INTEGRACAO', $customerId)
            ->where('LINK_HASH', $linkHash)
            ->where('METODO_PAGAMENTO', 'PIX')
            ->where('STATUS', 'PENDING')
            ->latest('DATA_VENCIMENTO')
            ->first();

        if ($pagamentoExistente) {
            // Já existe pagamento pendente — não cria novo, apenas retorna dados
            $detalhe           = $this->asaasService->getQRCodeById($pagamentoExistente->ID_PAGAMENTO_INTEGRACAO);
            $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);

            $dataFormatada = $pagamentoExistente->DATA_VENCIMENTO
                ? Carbon::parse($pagamentoExistente->DATA_VENCIMENTO)->format('d/m/Y')
                : null;

            return response()->json([
                'success'           => true,
                'detalhe_pagamento' => $detailedResponses,
                'id_pagamento'      => $pagamentoExistente->ID_PAGAMENTO_INTEGRACAO,
                'proposta'          => new PropostalIndexResource($propostal),
                'data_vencimento'   => $dataFormatada,
                'reutilizado'       => true, // opcional para controle
            ]);
        }

        $payload = [
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
                    'LINK_HASH'               => $linkHash,
                ]
            );

            if ($statusPagamento !== 'CONFIRMED') {
                $allConfirmed = false;
            }

            $detalhe           = $this->asaasService->getQRCodeById($response['data']['id']);
            $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);
        } else {
            $allConfirmed = false;
        }

        if ($allConfirmed) {
            $propostal->update([
                'CONTRATO_STATUS'         => 'Ativo',
                'PROPOSTA_STATUS'         => 'Aprovado',
                'PROPOSTA_CREDITO_STATUS' => 'Pagamento Efetuado',
            ]);
        }

        if (! $allConfirmed) {
            $propostal->update([
                'CONTRATO_STATUS'         => 'Pendente',
                'PROPOSTA_STATUS'         => 'Aprovado',
                'PROPOSTA_CREDITO_STATUS' => utf8_decode('Pagamento em Análise'),
            ]);
        }

        $dataVencimentoAnterior = PropostalPayments::where('LINK_HASH', $linkHash)
            ->latest('DATA_VENCIMENTO')
            ->value('DATA_VENCIMENTO');
        $dataFormatada = $dataVencimentoAnterior
            ? Carbon::parse($dataVencimentoAnterior)->format('d/m/Y')
            : null;

        if (! empty($detailedResponses)) {
            return response()->json([
                'success'           => true,
                'detalhe_pagamento' => $detailedResponses,
                'id_pagamento'      => $response['data']['id'],
                'proposta'          => new PropostalIndexResource($propostal),
                'data_vencimento'   => $dataFormatada,
            ]);
        }
    }

    public function criarPagamentoBoleto(Request $request, $linkHash)
    {
        list($customerId, $propostal) = $this->initCheckout($request, $linkHash);

        $pagamentoExistente = PropostalPayments::where('ID_USUARIO_INTEGRACAO', $customerId)
            ->where('LINK_HASH', $linkHash)
            ->where('METODO_PAGAMENTO', 'BOLETO')
            ->where('STATUS', 'PENDING')
            ->latest('DATA_VENCIMENTO')
            ->first();

        if ($pagamentoExistente) {
            // Já existe pagamento pendente — não cria novo, apenas retorna dados
            $detalhe           = $this->asaasService->getLineBoletoById($pagamentoExistente->ID_PAGAMENTO_INTEGRACAO);
            $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);

            $dataFormatada = $pagamentoExistente->DATA_VENCIMENTO
                ? Carbon::parse($pagamentoExistente->DATA_VENCIMENTO)->format('d/m/Y')
                : null;
            $linkBoleto = $this->asaasService->getPaymentById($pagamentoExistente->ID_PAGAMENTO_INTEGRACAO);

            return response()->json([
                'success'           => true,
                'detalhe_pagamento' => $detailedResponses,
                'id_pagamento'      => $pagamentoExistente->ID_PAGAMENTO_INTEGRACAO,
                'proposta'          => new PropostalIndexResource($propostal),
                'data_vencimento'   => $dataFormatada,
                'link_boleto'       => $linkBoleto['bankSlipUrl'] ?? null,
            ]);
        }

        $payload = [
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
                    'LINK_HASH'               => $linkHash,
                ]
            );

            $dataFormatada = $payment->DATA_VENCIMENTO
                ? Carbon::parse($payment->DATA_VENCIMENTO)->format('d/m/Y')
                : null;

            if ($statusPagamento !== 'CONFIRMED') {
                $allConfirmed = false;
            }

            $detalhe           = $this->asaasService->getLineBoletoById($response['data']['id']);
            $detailedResponses = is_array($detalhe) ? $detalhe : json_decode(json_encode($detalhe), true);
        } else {
            $allConfirmed = false;
        }

        if ($allConfirmed) {
            $propostal->update([
                'CONTRATO_STATUS'         => 'Ativo',
                'PROPOSTA_STATUS'         => 'Aprovado',
                'PROPOSTA_CREDITO_STATUS' => 'Pagamento Efetuado',
            ]);
        }

        if (! $allConfirmed) {
            $propostal->update([
                'CONTRATO_STATUS'         => 'Pendente',
                'PROPOSTA_STATUS'         => 'Aprovado',
                'PROPOSTA_CREDITO_STATUS' => utf8_decode('Pagamento em Análise'),
            ]);
        }

        if (! empty($detailedResponses)) {
            $linkBoleto = $this->asaasService->getPaymentById($response['data']['id']);

            return response()->json([
                'success'           => true,
                'detalhe_pagamento' => $detailedResponses,
                'id_pagamento'      => $response['data']['id'],
                'link_boleto'       => $linkBoleto['bankSlipUrl'] ?? null,
                'proposta'          => new PropostalIndexResource($propostal),
                'data_vencimento'   => $dataFormatada,
            ]);
        }
    }

    public function criarPagamentoCartao(Request $request, $linkHash)
    {
        list($customerId, $propostal) = $this->initCheckout($request, $linkHash);

        $pagamentoExistente = PropostalPayments::where('ID_USUARIO_INTEGRACAO', $customerId)
            ->where('LINK_HASH', $linkHash)
            ->where('METODO_PAGAMENTO', 'CREDIT_CARD')
            ->where('STATUS', 'PENDING')
            ->latest('DATA_VENCIMENTO')
            ->first();

        if (!$pagamentoExistente) {
            $payloads = $this->buildPayloadPayment($propostal, $request) ?? [];

            if (empty($payloads)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados do payload inválidos',
                ], 400);
            }

            $detalhesPagamentos = [];
            $idPagamento        = [];

            if ($payloads['imovel']) {
                $asaasResponse = $this->asaasService->createPayment($payloads['imovel']);
                $paymentId     = $asaasResponse['data']['id'] ?? null;

                if (! $paymentId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro ao criar cobrança no Asaas',
                    ], 500);
                }

                // $payWithCardResponse = $this->asaasService->payWithCreditCard($paymentId, $payloads['imovel']);

                $responseData = is_array($asaasResponse)
                    ? $asaasResponse
                    : json_decode(json_encode($asaasResponse), true);

                if (! isset($responseData['success']) || ! $responseData['success']) {
                    return response()->json([
                        'success'        => false,
                        'message'        => 'Erro ao processar pagamento com cartão',
                        'asaas_response' => $responseData,
                    ], 500);
                }

                $asaasPaymentId = $responseData['data']['id'] ?? null;
                $idPagamento[]  = $asaasPaymentId;

                if (! $asaasPaymentId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ID do pagamento retornado é inválido',
                    ], 500);
                }

                // Busca detalhes do pagamento
                $detalhes             = $this->asaasService->getPaymentById($asaasPaymentId);
                $detalhesArray        = is_array($detalhes) ? $detalhes : json_decode(json_encode($detalhes), true);
                $detalhesPagamentos[] = $detalhesArray;

                // Insere pagamento na base
                $paymentData                            = $this->buildInsertPaymentPropostal($propostal, $responseData, $customerId, $request);
                $paymentData['ID_PAGAMENTO_INTEGRACAO'] = $asaasPaymentId;
                $paymentData['LINK_HASH']               = $linkHash;

                PropostalPayments::create($paymentData);
            }

            if ($payloads['setup']) {
                $asaasResponse = $this->asaasService->createPayment($payloads['setup']);
                $paymentId     = $asaasResponse['data']['id'] ?? null;

                if (! $paymentId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro ao criar cobrança no Asaas',
                    ], 500);
                }

                // $payWithCardResponse = $this->asaasService->payWithCreditCard($paymentId, $payloads['imovel']);

                $responseData = is_array($asaasResponse)
                    ? $asaasResponse
                    : json_decode(json_encode($asaasResponse), true);

                if (! isset($responseData['success']) || ! $responseData['success']) {
                    return response()->json([
                        'success'        => false,
                        'message'        => 'Erro ao processar pagamento com cartão',
                        'asaas_response' => $responseData,
                    ], 500);
                }

                $asaasPaymentId = $responseData['data']['id'] ?? null;
                $idPagamento[]  = $asaasPaymentId;

                if (! $asaasPaymentId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ID do pagamento retornado é inválido',
                    ], 500);
                }

                // Busca detalhes do pagamento
                $detalhes             = $this->asaasService->getPaymentById($asaasPaymentId);
                $detalhesArray        = is_array($detalhes) ? $detalhes : json_decode(json_encode($detalhes), true);
                $detalhesPagamentos[] = $detalhesArray;

                // Insere pagamento na base
                $paymentData                            = $this->buildInsertPaymentPropostal($propostal, $responseData, $customerId, $request);
                $paymentData['ID_PAGAMENTO_INTEGRACAO'] = $asaasPaymentId;
                $paymentData['LINK_HASH']               = $linkHash;

                PropostalPayments::create($paymentData);
            }

            // Atualiza status da proposta
            $propostal->update([
                'CONTRATO_STATUS'         => 'Ativo',
                'PROPOSTA_STATUS'         => 'Aprovado',
                'PROPOSTA_CREDITO_STATUS' => 'Pagamento Efetuado',
            ]);

            return response()->json([
                'success'             => true,
                'detalhes_pagamentos' => $detalhesPagamentos,
                'ids_pagamentos'      => $idPagamento,
                'propostas'           => new PropostalIndexResource($propostal),
            ]);
        }
    }

    public function cancelarPagamento($paymentId, $linkHash)
    {
        $paymentStatus = $this->asaasService->getPaymentById($paymentId);

        if (empty($paymentStatus) || ! isset($paymentStatus['status'])) {
            return response()->json([
                'success'  => false,
                'mensagem' => 'Pagamento não encontrado na API do Asaas.',
            ], 404);
        }

        $propostalPayment = PropostalPayments::where('LINK_HASH', "=", $linkHash)->first();

        if (! $propostalPayment) {
            return response()->json([
                'success'  => false,
                'mensagem' => 'Pagamento não encontrado no sistema local.',
            ], 404);
        }

        if ($paymentStatus['status'] === 'PENDING' && $propostalPayment['STATUS'] === 'PENDING') {
            // Cancela no Asaas
            $cancelado = $this->asaasService->deletePayment($paymentId);

            // Apaga do banco se a exclusão foi bem-sucedida
            if ($cancelado) {
                $propostalPayment->delete();

                return response()->json([
                    'success'      => true,
                    'mensagem'     => 'Pagamento cancelado com sucesso.',
                    'pagamento_id' => $paymentId,
                    'link_hash'    => $linkHash,
                ]);
            } else {
                return response()->json([
                    'success'  => false,
                    'mensagem' => 'Erro ao cancelar o pagamento na API do Asaas.',
                ], 500);
            }
        }

        // Caso o status não permita cancelamento
        return response()->json([
            'success'      => false,
            'mensagem'     => 'Pagamento não está pendente e não pode ser cancelado.',
            'status_asaas' => $paymentStatus['status'],
            'status_local' => $propostalPayment['STATUS'],
        ], 400);
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
}