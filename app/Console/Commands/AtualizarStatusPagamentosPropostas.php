<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Services\Propostas\PropostaPagamentoStatusUpdater;
use Illuminate\Console\Command;

class AtualizarStatusPagamentosPropostas extends Command
{
    protected $signature = 'propostas:atualizar-pagamentos';

    protected $description = 'Consulta API Asaas e atualiza status de pagamentos e propostas.';

    protected PropostaPagamentoStatusUpdater $atualizador;

    public function __construct(PropostaPagamentoStatusUpdater $atualizador)
    {
        parent::__construct();
        $this->atualizador = $atualizador;
    }

    public function handle()
    {
        $this->info('Iniciando atualização de status de pagamentos...');

        try {
            $this->atualizador->atualizarPagamentos();
            $this->info('Atualização finalizada com sucesso.');
        } catch (\Exception $e) {
            $this->error('Erro durante a atualização: ' . $e->getMessage());
        }

        return 0;
    }
}
