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
    public function __construct(protected AsaasClientService $asaasClient)
    {
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

                $statusAccept = ['CONFIRMED', 'RECEIVED'];

                if (isset($response['status']) && in_array($response['status'], $statusAccept)) {
                    // Atualiza pagamento para pago
                    $pagamento->STATUS           = $response['status'];
                    $pagamento->METODO_PAGAMENTO = $response['billingType'];
                    $pagamento->DATA_PAGAMENTO   = $response['clientPaymentDate'];
                    $pagamento->save();

                    // Atualiza proposta relacionada
                    $proposta = Propostal::find($pagamento->ID_MOVI);

                    if ($proposta) {
                        $proposta->CONTRATO_STATUS = 'Ativo';
                        $proposta->PROPOSTA_STATUS = 'Pagamento efetuado';
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
