<?php

declare(strict_types = 1);

namespace App\Services\Propostas;

use App\Models\Proposta;
use App\Models\Propostal\Propostal;
use App\Models\Propostal\PropostalPayments;
use App\Services\Asaas\AsaasClientService;
use Illuminate\Support\Facades\Log;

class PropostaPagamentoStatusUpdater
{
    protected AsaasClientService $asaasClient;

    public function __construct(AsaasClientService $asaasClient)
    {
        $this->asaasClient = $asaasClient;
    }

    /**
     * Verifica todos os pagamentos pendentes e atualiza status
     */
    public function atualizarPagamentos(): void
    {
        // Buscar pagamentos que ainda não estão marcados como pagos
        $pagamentosPendentes = PropostalPayments::where('STATUS', '<>', 'CONFIRMED')->get();

        foreach ($pagamentosPendentes as $pagamento) {
            try {
                $idPagamentoIntegracao = $pagamento->ID_PAGAMENTO_INTEGRACAO;

                if (! $idPagamentoIntegracao) {
                    Log::warning("Pagamento {$pagamento->id} não tem ID_PAGAMENTO_INTEGRACAO.");

                    continue;
                }

                // Consulta API Asaas
                $response = $this->asaasClient->getPaymentById($idPagamentoIntegracao);

                if (isset($response['status']) && $response['status'] === 'CONFIRMED') {
                    // Atualiza pagamento para pago
                    $pagamento->status = 'CONFIRMED';
                    $pagamento->save();

                    // Atualiza proposta relacionada
                    $proposta = Propostal::find($pagamento->ID_MOVI);

                    if ($proposta) {
                        $proposta->contrato_status = 'Ativo';
                        $proposta->proposta_status = 'Pagamento efetuado';
                        $proposta->forma_pagamento = $response['billingType'] ?? $proposta->forma_pagamento;
                        $proposta->save();
                    } else {
                        Log::error("Proposta ID {$pagamento->ID_MOVI} não encontrada.");
                    }
                } else {
                    Log::info("Pagamento {$pagamento->id} não está pago ainda. Status: " . ($response['status'] ?? 'desconhecido'));
                }
            } catch (\Exception $e) {
                Log::error("Erro ao atualizar pagamento {$pagamento->id}: " . $e->getMessage());
            }
        }
    }
}
