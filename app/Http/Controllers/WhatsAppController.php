<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Delinquencies;
use App\Models\Propostal\Propostal;
use App\Models\RealEstateSector;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends Controller
{
    public function __construct(protected WhatsAppService $whatsAppService)
    {
    }

    /**
     * Envia uma mensagem de WhatsApp com base em um tipo predefinido.
     *
     * @param string $messageType
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessageByType(Request $request, string $messageType, string $linkHash)
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

        // Recupera o conteúdo da mensagem (ou dados para template)
        $messages = $this->getMessageContent($messageType, $linkHash, $data);

        if ($messages === null) {
            return response()->json([
                'message' => "Tipo de mensagem '$messageType' inválido ou sem conteúdo configurado.",
            ], 400);
        }

        // 🔹 Caso especial: se for “proposta”, envia template diretamente
        if ($messageType === 'proposta') {
            $templateSid = 'HXc07f067606e2063e65dcbadef61e0938'; // <-- seu template aprovado no Twilio

            $sent = $this->whatsAppService->sendTemplateMessage(
                $to,
                $templateSid,
                $messages // aqui “$messages” já são as variáveis do template
            );

            return $sent
                ? response()->json(['message' => 'Template enviado com sucesso!'], 200)
                : response()->json(['message' => 'Falha ao enviar template. Verifique os logs.'], 500);
        }

        if ($messageType === 'proposta_inicial') {
            $templateSid = 'HXc4b0f8d7cf4e0b6ebaba646d12f006ce'; // <-- seu template aprovado no Twilio

            $sent = $this->whatsAppService->sendTemplateMessage(
                $to,
                $templateSid,
                $messages // aqui “$messages” já são as variáveis do template
            );

            return $sent
                ? response()->json(['message' => 'Template enviado com sucesso!'], 200)
                : response()->json(['message' => 'Falha ao enviar template. Verifique os logs.'], 500);
        }

         if ($messageType === 'pagamento_inicial') {
            $templateSid = 'HX6984983bfe4eb2f67e5ce3176bdcda94'; // <-- seu template aprovado no Twilio

            $sent = $this->whatsAppService->sendTemplateMessage(
                $to,
                $templateSid,
                $messages // aqui “$messages” já são as variáveis do template
            );

            return $sent
                ? response()->json(['message' => 'Template enviado com sucesso!'], 200)
                : response()->json(['message' => 'Falha ao enviar template. Verifique os logs.'], 500);
        }

        if ($messageType === 'abertura_inadimplencia') {
            $templateSid = 'HX46bd1629a8dc071078d87bb3a0bff6e4'; // <-- seu template aprovado no Twilio

            $sent = $this->whatsAppService->sendTemplateMessage(
                $to,
                $templateSid,
                $messages // aqui “$messages” já são as variáveis do template
            );

            return $sent
                ? response()->json(['message' => 'Template enviado com sucesso!'], 200)
                : response()->json(['message' => 'Falha ao enviar template. Verifique os logs.'], 500);
        }

        // 🔹 Caso seja outro tipo (ex: welcome, custom)
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $allSent = true;
        $messageIndex = 0;

        foreach ($messages as $msgContent) {
            $sent = false;

            if ($mediaUrl && $messageIndex === 0) {
                $sent = $this->whatsAppService->sendMediaMessage($to, $mediaUrl, $msgContent);
            } else {
                $sent = $this->whatsAppService->sendMessage($to, $msgContent);
            }

            if (!$sent) {
                $allSent = false;
                Log::error("Falha ao enviar mensagem '$messageType' para $to. Conteúdo: " . $msgContent);
                break;
            }

            $messageIndex++;
        }

        return $allSent
            ? response()->json(['message' => 'Mensagem(ns) enviada(s) com sucesso!'], 200)
            : response()->json(['message' => 'Falha ao enviar uma ou mais mensagens.'], 500);
    }

    /**
     * Monta o conteúdo da mensagem ou dados do template.
     */
    protected function getMessageContent(string $messageType, string $linkHash, array $data = []): string|array|null
    {
        $propostal = Propostal::where('LINK_HASH', '=', $linkHash)->first();
        $inadimplencia = Delinquencies::where('CONTRATO_ID', '=', $propostal->ID)->first();

        if (!$propostal) {
            return null;
        }

        switch ($messageType) {
            case 'welcome':
                $name = $data['name'] ?? 'cliente';
                return "Bem-vindo(a), $name! Agradecemos por se cadastrar em nossa plataforma.";

            case 'proposta':
                $imobiliariaInfo = RealEstateSector::where('ID', '=', $propostal->ID_IMOBILIARIA)->first();
                $nomeCompleto = $propostal->PESSOA_NOME ?? 'Cliente';
                $nomeImobiliaria = $imobiliariaInfo['RAZAO'] ?? 'A imobiliária';
                $enderecoLocacao = "{$propostal->IMOVEL_ENDERECO}, {$propostal->IMOVEL_BAIRRO}, {$propostal->IMOVEL_NUMERO}, {$propostal->IMOVEL_CIDADE} - {$propostal->IMOVEL_ESTADO}" ?? 'Endereço não informado';
                $telefone = $propostal->PESSOA_TELEFONE ?? 'não informado';
                $email = $propostal->PESSOA_EMAIL ?? 'não informado';

                // 🔹 Retorna apenas variáveis para o template do Twilio
                return [
                    "1" => $nomeCompleto,
                    "2" => $nomeImobiliaria,
                    "3" => $enderecoLocacao,
                    "4" => $nomeCompleto,
                    "5" => $telefone,
                    "6" => $email,
                ];

            case 'proposta_inicial':
                $nomeCompleto = $propostal->PESSOA_NOME;
                $linkAtivacao = $propostal->LINK_FACIAL;

                // 🔹 Retorna apenas variáveis para o template do Twilio
                return [
                    "1" => $nomeCompleto,
                    "2" => $linkAtivacao,
                ];

            case 'pagamento_inicial':
                $nomeCompleto = $propostal->PESSOA_NOME;
                $linkAtivacao = $propostal->LINK_FACIAL;

                // 🔹 Retorna apenas variáveis para o template do Twilio
                return [
                    "1" => $nomeCompleto,
                    "2" => $linkAtivacao,
                ];

         case 'abertura_inadimplencia':
            $nomeCompleto = $propostal->PESSOA_NOME;
            $valorOriginal = $inadimplencia->VALOR_ORIGINAL;
            $vencimentoOriginal = $inadimplencia->VENCIMENTO_ORIGINAL;
            $tipoInadimplencia = $inadimplencia->TIPO_CONTA;

            // 🔹 Converte o valor para float antes de formatar
            $valorOriginalNumerico = floatval($valorOriginal);
            $valorOriginalFormatado = 'R$ ' . number_format($valorOriginalNumerico, 2, ',', '.');

            // 🔹 Formata a data para padrão brasileiro (d/m/Y)
            $vencimentoOriginalFormatado = '';
            if (!empty($vencimentoOriginal)) {
                $vencimentoOriginalFormatado = date('d/m/Y', strtotime($vencimentoOriginal));
            }

            // 🔹 Retorna variáveis para o template do Twilio
            return [
                "1" => $nomeCompleto,
                "2" => $valorOriginalFormatado,
                "3" => $vencimentoOriginalFormatado,
                "4" => utf8_encode($tipoInadimplencia),
            ];

            case 'custom':
                return $data['message'] ?? null;

            default:
                return null;
        }
    }
}
