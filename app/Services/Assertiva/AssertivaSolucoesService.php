<?php

declare(strict_types = 1);

namespace App\Services\Assertiva;

use App\Http\Resources\Assertiva\AssertivaResource;
use App\Models\ScoreResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

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

        // Implementar o insert na tabela: ASSERTIVA_SCORE_RETORNO
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
                'PRODUTO'               => mb_convert_encoding((string) $returnResponse['cabecalho']['produto'], 'ISO-8859-1'),
                'FUNCIONALIDADE'        => mb_convert_encoding((string) $returnResponse['cabecalho']['funcionalidade'], 'ISO-8859-1'),
                'PROTOCOLO'             => $returnResponse['cabecalho']['protocolo'],
                'SCORE_CLASSE'          => $returnResponse['resposta']['score']['classe'],
                'SCORE_FAIXA_TITULO'    => mb_convert_encoding((string) $returnResponse['resposta']['score']['faixa']['titulo'], 'ISO-8859-1'),
                'SCORE_FAIXA_DESCRICAO' => mb_convert_encoding((string) $returnResponse['resposta']['score']['faixa']['descricao'], 'ISO-8859-1'),
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
}
