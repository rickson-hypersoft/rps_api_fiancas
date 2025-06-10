<?php

declare(strict_types = 1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class WhatsAppService
{
    protected $twilioClient;

    protected $fromWhatsAppNumber;

    public function __construct()
    {
        $sid   = env('TWILIO_ACCOUNT_SID');
        $token = env('TWILIO_AUTH_TOKEN');

        if (empty($sid) || empty($token)) {
            throw new Exception('Credenciais Twilio não configuradas. Verifique .env.');
        }

        $this->twilioClient       = new Client($sid, $token);
        $this->fromWhatsAppNumber = env('TWILIO_WHATSAPP_NUMBER');

        if (empty($this->fromWhatsAppNumber)) {
            throw new Exception('Número WhatsApp da Twilio não configurado. Verifique TWILIO_WHATSAPP_NUMBER no .env.');
        }
    }

    /**
     * Envia uma mensagem de texto para o WhatsApp.
     *
     * @param string $to O número de telefone do destinatário (ex: +5511987654321).
     * @param string $message O conteúdo da mensagem.
     * @return bool True se a mensagem foi enviada com sucesso, False caso contrário.
     */
    public function sendMessage(string $to, string $message): bool
    {
        // Validar o formato do número de destino para WhatsApp
        if (! str_starts_with($to, 'whatsapp:')) {
            $to = 'whatsapp:' . $to;
        }

        try {
            $message = $this->twilioClient->messages->create(
                $to,
                [
                    "from" => $this->fromWhatsAppNumber,
                    "body" => $message,
                ]
            );

            Log::info("Mensagem WhatsApp enviada. SID: " . $message->sid . " para: " . $to);

            return true;
        } catch (Exception $e) {
            Log::error("Erro ao enviar mensagem WhatsApp para {$to}: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Envia uma mensagem de mídia (imagem, vídeo, PDF) para o WhatsApp.
     * Note: URLs de mídia devem ser acessíveis publicamente.
     *
     * @param string $to O número de telefone do destinatário (ex: +5511987654321).
     * @param string $mediaUrl A URL pública do arquivo de mídia.
     * @param string|null $body Opcional: Texto da mensagem junto com a mídia.
     * @return bool True se a mensagem foi enviada com sucesso, False caso contrário.
     */
    public function sendMediaMessage(string $to, string $mediaUrl, ?string $body = null): bool
    {
        if (! str_starts_with($to, 'whatsapp:')) {
            $to = 'whatsapp:' . $to;
        }

        try {
            $options = [
                "from"     => $this->fromWhatsAppNumber,
                "mediaUrl" => [$mediaUrl], // MediaUrl deve ser um array de URLs
            ];

            if ($body) {
                $options["body"] = $body;
            }

            $message = $this->twilioClient->messages->create(
                $to,
                $options
            );

            Log::info("Mensagem WhatsApp com mídia enviada. SID: " . $message->sid . " para: " . $to);

            return true;
        } catch (Exception $e) {
            Log::error("Erro ao enviar mensagem WhatsApp com mídia para {$to}: " . $e->getMessage());

            return false;
        }
    }

    // Você pode adicionar outros métodos aqui, como enviar templates, etc.
}
