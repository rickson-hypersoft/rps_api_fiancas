<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Services\Assertiva\AssertivaSolucoesService;
use Illuminate\Console\Command;

class VerificarStatusFacial extends Command
{
    protected $signature = 'facial:verificar-status';

    protected $description = 'Verifica status facial via API Assertiva e envia WhatsApp se aprovado.';

    public function __construct(protected AssertivaSolucoesService $atualizador)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Verificando status facial...');

        try {
            $this->atualizador->verificaStatusFacial();
            $this->info('Verificação concluída.');
        } catch (\Exception $e) {
            $this->error('Erro ao verificar status facial: ' . $e->getMessage());
        }

        return 0;
    }
}
