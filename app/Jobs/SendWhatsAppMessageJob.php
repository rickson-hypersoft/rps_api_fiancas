<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Services\WhatsAppService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue; // Importe o seu serviço
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels; // Para mensagens com mídia

    /**
     * Create a new job instance.
     *
     * @param string $to Número de telefone do destinatário (ex: +5511987654321).
     * @param string $message Conteúdo da mensagem.
     * @param string|null $mediaUrl Opcional: URL da mídia (imagem, vídeo, etc.).
     */
    public function __construct(protected string $to, protected string $message, protected ?string $mediaUrl = null)
    {
    }

    /**
     * Execute the job.
     *
     * @param WhatsAppService $whatsAppService O Laravel injetará automaticamente a instância do serviço.
     */
    public function handle(WhatsAppService $whatsAppService): void
    {
        try {
            if ($this->mediaUrl !== null && $this->mediaUrl !== '' && $this->mediaUrl !== '0') {
                $whatsAppService->sendMediaMessage($this->to, $this->mediaUrl, $this->message);
            } else {
                $whatsAppService->sendMessage($this->to, $this->message);
            }
        } catch (Exception $e) {
            // Loga o erro, mas não o relança para não travar a fila.
            Log::error("Falha no Job SendWhatsAppMessageJob para {$this->to}: " . $e->getMessage());
            // Você pode adicionar lógica aqui para re-tentar, notificar, etc.
        }
    }
}
