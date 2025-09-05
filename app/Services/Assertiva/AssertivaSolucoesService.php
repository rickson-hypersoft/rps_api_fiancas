<?php

declare(strict_types = 1);

namespace App\Services\Assertiva;

use App\Http\Resources\Assertiva\AssertivaResource;
use App\Models\History;
use App\Models\Propostal\Propostal;
use App\Models\ScoreResponse;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AssertivaSolucoesService
{
    protected string $baseUrl = "https://api.assertivasolucoes.com.br/score/v3";

    protected function fetchAccessToken(): string
    {
        $username = config('services.assertiva.client_id');
        $password = config('services.assertiva.client_secret');

        $url = 'https://api.assertivasolucoes.com.br/oauth2/v3/token';

        $response = Http::asForm()
            ->withBasicAuth($username, $password)
            ->post($url, [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            throw new Exception('Erro ao obter token da Assertiva: ' . $response->body());
        }

        return $response->json('access_token');
    }

    public function checkScore(string $document, int $finaly = 2)
    {
        $token = $this->fetchAccessToken();

        $document = preg_replace('/\D/', '', $document);

        $existingInTable = ScoreResponse::where('PESSOA_DOC', '=', $document)
            ->where('EXPIRA_EM', '>=', Carbon::today()->toDateString())
            ->first();

        if ($existingInTable) {
            return new AssertivaResource($existingInTable);
        }

        $tipo_consulta = '';

        if (strlen((string) $document) === 11) {
            $tipo_consulta = 'pf';
        } elseif (strlen((string) $document) === 14) {
            $tipo_consulta = 'pj';
        } else {
            throw new \InvalidArgumentException('Documento inválido.');
        }

        $url = "{$this->baseUrl}/{$tipo_consulta}/credito/{$document}";

        $query = ['idFinalidade' => $finaly];

        $response = Http::withToken($token)
            ->acceptJson()
            ->get($url, $query);

        if ($response->failed()) {
            throw new Exception('Erro ao consulta Score Assertiva: ' . $response->body());
        }

        $return = $response->json();

        try {
            return response()->json($this->insertResponseReturnInTable($return, $document, $tipo_consulta));
        } catch (Exception $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                400
            );
        }
    }

    private function insertResponseReturnInTable(array $returnResponse, string $document, string $tipo_consulta)
    {
        $dataHora = explode(' ', (string) $returnResponse['cabecalho']['dataHora']);
        $data     = Carbon::createFromFormat('d/m/Y', $dataHora[0]);
        $hora     = Carbon::rawCreateFromFormat('H:i:s', $dataHora[1]);

        try {
            $response = ScoreResponse::create([
                'PESSOA_DOC'            => $document,
                'DATA'                  => $data->format('Y-m-d'),
                'HORA'                  => $hora->format('H:i:s'),
                'PRODUTO'               => $returnResponse['cabecalho']['produto'],
                'FUNCIONALIDADE'        => utf8_decode($returnResponse['cabecalho']['funcionalidade']),
                'PROTOCOLO'             => $returnResponse['cabecalho']['protocolo'],
                'SCORE_CLASSE'          => $returnResponse['resposta']['score']['classe'],
                'SCORE_FAIXA_TITULO'    => utf8_decode($returnResponse['resposta']['score']['faixa']['titulo']),
                'SCORE_FAIXA_DESCRICAO' => utf8_decode($returnResponse['resposta']['score']['faixa']['descricao']),
                'SCORE_PONTOS'          => $returnResponse['resposta']['score']['pontos'],
                'RENDA_PRESUMIDA'       => $returnResponse['resposta']['rendaPresumida']['valor'] ?? 0,
                'EXPIRA_EM'             => $data->addDays(7)->format('Y-m-d'),
                'TIPO_CONSULTA'         => $tipo_consulta,
                'FATURAMENTO_ESTIMADO'  => $returnResponse['resposta']['faturamentoEstimado']['valor']
                             ?? 0,
                'ACOES_ULT_OCORRENCIA' => ! empty($returnResponse['resposta']['acoes']) && isset($returnResponse['resposta']['acoes']['ultimaOcorrencia'])
                    ? $returnResponse['resposta']['acoes']['ultimaOcorrencia']
                    : null,

                'ACOES_VALOR_TOTAL' => ! empty($returnResponse['resposta']['acoes']) && isset($returnResponse['resposta']['acoes']['valorTotal'])
                    ? $returnResponse['resposta']['acoes']['valorTotal']
                    : null,

                'ACOES_QTD' => ! empty($returnResponse['resposta']['acoes']) && isset($returnResponse['resposta']['acoes']['qtdAcoes'])
                    ? $returnResponse['resposta']['acoes']['qtdAcoes']
                    : null,
            ]);

            return new AssertivaResource($response);
        } catch (Exception $e) {
            return throw new Exception('Erro ao criar registro na tabela: ASSERTIVA_SCORE_RETORNO: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createOrderSignature($userData)
    {
        $token              = $this->fetchAccessToken();
        $dadosLinkUploadPDF = $this->getLinkUploadPDF();

        $urlUpload      = $dadosLinkUploadPDF['data']['links'][0]['url'];
        $chaveUploadPDF = $dadosLinkUploadPDF['data']['links'][0]['chave'];

        if (! $this->uploadPDFAWS($userData->LINK_HASH, $urlUpload)) {
            return response()->json(['message' => 'Problemas ao enviar PDF pra o servidor da AWS'], 400);
        }

        $body = $this->getBodyCreateSignature($userData, $chaveUploadPDF);

        $url = "https://api.assertivasolucoes.com.br/autentica/v1/jornadas/pedidos";

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, $body);

        if ($response->failed()) {
            throw new Exception('Erro ao criar parte Assertiva: ' . $response->body());
        }

        return $response->json();
    }

    public function getLink($protocol)
    {
        $token     = $this->fetchAccessToken();
        $protocolo = $protocol->PROTOCOLO_PARTE_FACIAL;

        $url = 'https://api.assertivasolucoes.com.br/autentica/v1/jornadas/partes/gerar-link?protocolo=' . $protocolo;

        $response = Http::withToken($token)
            ->acceptJson()
            ->get($url);

        if ($response->failed()) {
            throw new Exception('Erro ao consulta Link Assertiva: ' . $response->body());
        }

        return $response->json();
    }

    private function getBodyCreateSignature($userData, $chave): array
    {
        return [
            "anexosGlobais" => [
                "anexosFluxo" => [],
                "anexos"      => [],
            ],
            "partes" => [
                [
                    "perfilId" => "0e7680b0-a528-4275-8711-fed682dc5d02",
                    "fluxoId"  => "5b4028f9-4060-4314-9fab-a379540e98da",
                    "campos"   => [
                        [
                            "id"    => "e99a9d68-1026-4830-912e-677906b0e8a3",
                            "valor" => "{$userData->PESSOA_NOME}",
                        ],
                        [
                            "id"    => "88cf8dea-0308-4ecf-b79a-0e69e987afdd",
                            "valor" => "{$userData->PESSOA_DOC}",
                        ],
                        [
                            "id"    => "69bb1749-aa9c-4947-809f-78368c681afc",
                            "valor" => "{$userData->PESSOA_TELEFONE}",
                        ],
                        [
                            "id"    => "c2cf1b39-65f0-4ac8-adc0-a3468c34e2d7",
                            "valor" => "{$userData->PESSOA_EMAIL}",
                        ],
                    ],
                    "anexos" => [
                        [
                            "artefato" => "Proposta de assinatura",
                            "nome"     => "Documentoassinatura",
                            "extensao" => "pdf",
                            "chave"    => "{$chave}",
                        ],
                    ],
                    "anexosFluxo" => [],
                ],
            ],
        ];
    }

    private function getLinkUploadPDF()
    {
        $token = $this->fetchAccessToken();

        $url = 'https://api.assertivasolucoes.com.br/autentica/v1/jornadas/arquivos/obter-link-upload-interno?quantidadeLinks=1';

        $response = Http::withToken($token)
            ->acceptJson()
            ->get($url);

        if ($response->failed()) {
            throw new Exception('Erro ao consulta Score Assertiva: ' . $response->body());
        }

        return $response->json();
    }

    private function uploadPDFAWS(string $linkHash, string $url)
    {
        $pdfPath = storage_path(implode(DIRECTORY_SEPARATOR, [
            'app',
            'public',
            'termos',
            $linkHash . '.pdf',
        ]));

        if (! file_exists($pdfPath)) {
            throw new Exception("Arquivo PDF não encontrado em: " . $pdfPath);
        }

        $stream = Utils::tryFopen($pdfPath, 'r');

        $response = Http::withHeaders([
            'Content-Type' => 'application/octet-stream',
        ])
            ->send('PUT', $url, [
                'body' => $stream,
            ]);

        if ($response->failed()) {
            throw new Exception('Erro ao fazer upload do PDF. Status: ' . $response->status());
        }

        return $response->status();
    }

    public function verificaStatusFacial()
    {
        $token     = $this->fetchAccessToken();
        $propostas = Propostal::whereNotNull('LINK_FACIAL')
            ->where('FACIAL', 0)
            ->get();

        if ($propostas->isEmpty()) {
            return response()->json(['message' => 'Nenhuma proposta pendente de facial.']);
        }

        foreach ($propostas as $proposta) {
            try {
                $parteId = $proposta->PARTE_ID;
                $url     = 'https://api.assertivasolucoes.com.br/autentica/v1/jornadas/partes/status-parte?parteId=' . $parteId;

                $response = Http::withToken($token)
                    ->acceptJson()
                    ->get($url);

                if ($response->failed()) {
                    Log::error("Erro ao consultar parteId {$parteId}: " . $response->body());

                    continue;
                }

                $data = $response->json();

                // Aprovado(s)
                if (isset($data['data']['status']) && strtolower($data['data']['status']) === 'aprovado(s)') {
                    $proposta->FACIAL              = 1;
                    $proposta->TERMO_ATIVO         = 1;
                    $proposta->DATA_ATIVACAO_TERMO = now()->format('Y-m-d');
                    $proposta->HORA_ATIVACAO_TERMO = now('H:i:s');
                    $proposta->save();

                    // Grava que o termo foi assinado pelo cliente
                    $historyData = [
                        'id_imobiliaria' => $proposta->ID_IMOBILIARIA,
                        'id_movi'        => $proposta->ID,
                        'movi'           => 'Contratos',
                        'data'           => now()->format('Y-m-d'),
                        'historico'      => 'Inquilino aceitou o termo',
                        'id_usuario'     => $proposta->$_COOKIE,
                        'hora'           => now()->format('H:i:s'),
                    ];

                    History::create($historyData);

                    $linkPagamento = "https://invicta.kinghost.net/fianca_front/ativacao/login/" . $proposta->LINK_HASH;
                    $mensagem      = "Parabéns! Sua validação facial foi aprovada. Para prosseguir, acesse o link de pagamento:\n$linkPagamento";

                    // $linkPagamento = "http://localhost:8001/ativacao/login/" . $proposta->LINK_HASH;
                    // $mensagem      = "Parabéns! Sua validação facial foi aprovada. Para prosseguir, acesse o link de pagamento: $linkPagamento";

                    if (strlen((string) $proposta->PESSOA_TELEFONE) === 11 && substr((string) $proposta->PESSOA_TELEFONE, 2, 1) === '9') {
                        $proposta->PESSOA_TELEFONE = substr((string) $proposta->PESSOA_TELEFONE, 0, 2) . substr((string) $proposta->PESSOA_TELEFONE, 3);
                    }

                    $numero = '+55' . $proposta->PESSOA_TELEFONE;

                    $whatsApp = new WhatsAppService();
                    $sent     = $whatsApp->sendMessage(
                        $numero,
                        $mensagem
                    );

                    if ($sent) {
                        Log::info("WhatsApp enviado para proposta {$proposta->ID}");
                    } else {
                        Log::warning("WhatsApp falhou para proposta {$proposta->ID}");
                    }
                } else {
                    Log::info("Status não aprovado para parteId {$parteId}: " . json_encode($data));
                }
            } catch (\Throwable $e) {
                Log::error("Erro ao processar proposta {$proposta->ID}: " . $e->getMessage());

                continue;
            }
        }

        return response()->json(['message' => 'Processo de verificação facial concluído.']);
    }
}
