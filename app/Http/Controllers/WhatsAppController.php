<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Propostal\Propostal;
use App\Models\RealEstateSector;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Envia uma ou mais mensagens de WhatsApp com base em um tipo predefinido.
     *
     * @param Request $request
     * @param string $messageType O tipo de mensagem a ser enviada (ex: 'welcome', 'proposta').
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessageByType(Request $request, string $messageType, $linkHash)
    {
        $rules = [
            'to'        => ['required', 'string', 'regex:/^\+?\d{10,15}$/'],
            'media_url' => 'nullable|url',
            'data'      => 'nullable|array',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'to.required'   => 'O número de destino é obrigatório.',
            'to.regex'      => 'Formato de número de telefone inválido. Use o código do país e DDD (ex: +5511987654321).',
            'media_url.url' => 'A URL da mídia deve ser uma URL válida.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados de entrada inválidos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $to       = $request->input('to');
        $mediaUrl = $request->input('media_url');
        $data     = $request->input('data', []);

        $messages = $this->getMessageContent($messageType, $data, $linkHash);

        if (empty($messages)) {
            return response()->json([
                'message' => "Tipo de mensagem '$messageType' inválido ou sem conteúdo configurado.",
            ], 400);
        }

        // Garante que $messages seja sempre um array para que o foreach funcione corretamente
        if (! is_array($messages)) {
            $messages = [$messages];
        }

        $allSent      = true;
        $messageIndex = 0; // Adiciona um contador para controlar a iteração

        foreach ($messages as $msgContent) {
            $sent = false;

            // A mídia (mediaUrl) será enviada apenas com a primeira mensagem
            if ($mediaUrl && $messageIndex === 0) {
                $sent = $this->whatsAppService->sendMediaMessage($to, $mediaUrl, $msgContent);
            } else {
                // Se estiver usando o Job (RECOMENDADO para assíncrono):
                // \App\Jobs\SendWhatsAppMessageJob::dispatch($to, $msgContent);
                $sent = $this->whatsAppService->sendMessage($to, $msgContent);
            }

            if (! $sent) {
                $allSent = false;
                Log::error("Falha ao enviar uma das mensagens do tipo '$messageType' para $to. Conteúdo: " . $msgContent);

                break; // Parar se uma falha ocorrer
            }
            $messageIndex++; // Incrementa o contador
        }

        if ($allSent) {
            // Se estiver usando Jobs, a resposta seria 202 Accepted.
            // return response()->json(['message' => 'Mensagem(ns) agendada(s) para envio.'], 202);
            return response()->json(['message' => 'Mensagem(ns) enviada(s) com sucesso!'], 200);
        } else {
            return response()->json(['message' => 'Falha ao enviar uma ou mais mensagens. Verifique os logs do servidor.'], 500);
        }
    }

    protected function getMessageContent(string $messageType, array $data = [], string $linkHash): string | array | null
    {
        $propostal = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();

        switch ($messageType) {
            case 'welcome':
                $name = $data['name'] ?? 'cliente';

                return "Bem-vindo(a), $name! Agradecemos por se cadastrar em nossa plataforma.";

            case 'proposta':
                $nomeCompleto    = $propostal->PESSOA_NOME ?? 'Cliente';
                $linkContrato    = "http://localhost:8001/ativacao/{$propostal->LINK_HASH}" ?? '#';
                $imobiliaria     = $propostal->ID_IMOBILIARIA ?? 'A imobiliária';
                $imobiliariaInfo = RealEstateSector::where('ID', '=', $imobiliaria)->firstOrFail();
                $nomeImobiliaria = $imobiliariaInfo['RAZAO'];

                $enderecoLocacao = "{$propostal->IMOVEL_ENDERECO}, {$propostal->IMOVEL_BAIRRO}, {$propostal->IMOVEL_NUMERO}, {$propostal->IMOVEL_CIDADE} - {$propostal->IMOVEL_ESTADO}" ?? 'endereço não informado';
                $telefone        = $propostal->PESSOA_TELEFONE ?? 'não informado';
                $email           = $propostal->PESSOA_EMAIL ?? 'não informado';

                $message1 = "Olá $nomeCompleto, Parabéns!! Falta pouco para ativar seu contrato da Invicta.\n\nPara finalizar a contratação dos serviços e alugar sem burocracia, acesse o link abaixo:\n$linkContrato";

                $message2 = "Olá $nomeCompleto\n\n" .
                    "Somos a Invicta empresa de garantia de fiança para locação.\n\n" .
                    "$nomeImobiliaria encaminhou seus dados para análise e sua solicitação foi aprovada!\n\n" .
                    "Para prosseguir, confirme os dados abaixo:\n\n" .
                    "Endereço para locação: $enderecoLocacao\n" .
                    "Nome completo: $nomeCompleto\n" .
                    "Telefone: $telefone\n" .
                    "E-mail: $email\n\n" .
                    "Se estiver correto, clique em 'sim'. Caso contrário, clique em 'não' e forneça os dados corretos.\n\n" .
                    "Um abraço,\n" .
                    "Time Loft Fiança";

                return [$message1, $message2];

            case 'custom':
                return $data['message'] ?? null;

            default:
                return null;
        }
    }
}