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

    protected string $token;

    public function __construct()
    {
        $this->token = config('services.assertiva.token');
    }

    public function checkScore(string $document, int $finaly = 2)
    {
        $document = preg_replace('/\D/', '', $document);

        $tipo_consulta = '';

        if (strlen((string) $document) == 11) {
            $tipo_consulta = 'PF';
        }

        if (strlen((string) $document) == 14) {
            $tipo_consulta = 'PJ';
        }

        /*
        $url  = "{$this->baseUrl}/{$type}/credito/{$document}";

        $query = ['idFinalidade' => $finaly];

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->get($url, $query);

        if ($response->failed()) {
            throw new Exception('Erro ao consulta Score Assertiva: ' . $response->body());
        }

        $return = $response->json();

        // Implementar o insert na tabela: ASSERTIVA_SCORE_RETORNO

        return $return;
        */

        $existingInTable = ScoreResponse::where('PESSOA_DOC', '=', $document)
            ->where('EXPIRA_EM', '>=', Carbon::today()->toDateString())
            ->first();

        if ($existingInTable) {
            return new AssertivaResource($existingInTable);
        }

        if ($document === '16054956620') {
            $return = [
                "cabecalho" => [
                    "entrada" => [
                        "documento" => "123.456.789-00",
                        "acoes"     => false,
                        "positivo"  => true,
                    ],
                ],
                "dataHora"       => now()->format('d/m/Y H:i:s'),
                "produto"        => "Score de Crédito Restritivo",
                "funcionalidade" => "Score Completo Sem Ações - Pessoa Física",
                "protocolo"      => "mock-protocolo-" . uniqid(),
                "reconsulta"     => false,
                "resposta"       => [
                    "score" => [
                        "classe" => "A",
                        "faixa"  => [
                            "titulo"    => "Baixo risco",
                            "descricao" => "Consumidores com essa classificação de score apresentam 75% de chances de honrar seus compromissos nos próximos 6 meses.",
                        ],
                        "pontos"           => 800,
                        "cadastroPositivo" => [
                            "suspenso"      => false,
                            "atrasoConsumo" => [
                                "descricao" => "Não há dados suficientes",
                                "valor"     => null,
                                "risco"     => null,
                            ],
                            "atrasoRecente" => [
                                "descricao" => "Não há dados suficientes",
                                "valor"     => null,
                                "risco"     => null,
                            ],
                            "relacionamentoCC" => [
                                "descricao" => "Possui cartão de crédito contratado à pelo menos um ano.",
                                "valor"     => 100,
                                "risco"     => "ALTO",
                            ],
                            "comprometimentoRenda" => [
                                "descricao" => "O indicador possui percentual de 0%, pois recentemente esse titular não comprometeu tanto a sua renda com produtos de crédito.",
                                "valor"     => 0,
                                "risco"     => "BAIXO",
                            ],
                        ],
                    ],
                    "protestosPublicos" => [
                        "erroProtestos"    => false,
                        "protestoCompleto" => true,
                        "list"             => [
                            [
                                "uf"       => "SP",
                                "cidade"   => "CAMPINAS",
                                "data"     => "22/09/2017",
                                "valor"    => 164075.63,
                                "cartorio" => "2. TABELIAO DE PROTESTO DE LETRAS E TITULOS",
                            ],
                        ],
                        "qtdProtestos"     => 1,
                        "ultimaOcorrencia" => "19/02/2018",
                        "valorTotal"       => 4662462.95,
                    ],
                    "registrosDebitos" => null,
                    "ultimasConsultas" => null,
                    "rendaPresumida"   => [
                        "valor" => 3000,
                    ],
                    "cheques" => [],
                    "acoes"   => [],
                ]];
        }

        if ($document === '50384631002') {
            $return = [
                "cabecalho" => [
                    "entrada" => [
                        "documento" => "123.456.789-00",
                        "acoes"     => false,
                        "positivo"  => true,
                    ],
                ],
                "dataHora"       => now()->format('d/m/Y H:i:s'),
                "produto"        => "Score de Crédito Restritivo",
                "funcionalidade" => "Score Completo Sem Ações - Pessoa Física",
                "protocolo"      => "mock-protocolo-" . uniqid(),
                "reconsulta"     => false,
                "resposta"       => [
                    "score" => [
                        "classe" => "A",
                        "faixa"  => [
                            "titulo"    => "Baixo risco",
                            "descricao" => "Consumidores com essa classificação de score apresentam 75% de chances de honrar seus compromissos nos próximos 6 meses.",
                        ],
                        "pontos"           => 600,
                        "cadastroPositivo" => [
                            "suspenso"      => false,
                            "atrasoConsumo" => [
                                "descricao" => "Não há dados suficientes",
                                "valor"     => null,
                                "risco"     => null,
                            ],
                            "atrasoRecente" => [
                                "descricao" => "Não há dados suficientes",
                                "valor"     => null,
                                "risco"     => null,
                            ],
                            "relacionamentoCC" => [
                                "descricao" => "Possui cartão de crédito contratado à pelo menos um ano.",
                                "valor"     => 100,
                                "risco"     => "ALTO",
                            ],
                            "comprometimentoRenda" => [
                                "descricao" => "O indicador possui percentual de 0%, pois recentemente esse titular não comprometeu tanto a sua renda com produtos de crédito.",
                                "valor"     => 0,
                                "risco"     => "BAIXO",
                            ],
                        ],
                    ],
                    "protestosPublicos" => [
                        "erroProtestos"    => false,
                        "protestoCompleto" => true,
                        "list"             => [
                            [
                                "uf"       => "SP",
                                "cidade"   => "CAMPINAS",
                                "data"     => "22/09/2017",
                                "valor"    => 164075.63,
                                "cartorio" => "2. TABELIAO DE PROTESTO DE LETRAS E TITULOS",
                            ],
                        ],
                        "qtdProtestos"     => 1,
                        "ultimaOcorrencia" => "19/02/2018",
                        "valorTotal"       => 4662462.95,
                    ],
                    "registrosDebitos" => null,
                    "ultimasConsultas" => null,
                    "rendaPresumida"   => [
                        "valor" => 3000,
                    ],
                    "cheques" => [],
                    "acoes"   => [],
                ],
            ];
        }

        if ($document === '51907352066') {
            $return = [
                "cabecalho" => [
                    "entrada" => [
                        "documento" => "123.456.789-00",
                        "acoes"     => false,
                        "positivo"  => true,
                    ],
                ],
                "dataHora"       => now()->format('d/m/Y H:i:s'),
                "produto"        => "Score de Crédito Restritivo",
                "funcionalidade" => "Score Completo Sem Ações - Pessoa Física",
                "protocolo"      => "mock-protocolo-" . uniqid(),
                "reconsulta"     => false,
                "resposta"       => [
                    "score" => [
                        "classe" => "F",
                        "faixa"  => [
                            "titulo"    => "Altíssimo risco",
                            "descricao" => "Consumidores com essa classificação de score apresentam 75% de chances de honrar seus compromissos nos próximos 6 meses.",
                        ],
                        "pontos"           => 400,
                        "cadastroPositivo" => [
                            "suspenso"      => false,
                            "atrasoConsumo" => [
                                "descricao" => "Não há dados suficientes",
                                "valor"     => null,
                                "risco"     => null,
                            ],
                            "atrasoRecente" => [
                                "descricao" => "Não há dados suficientes",
                                "valor"     => null,
                                "risco"     => null,
                            ],
                            "relacionamentoCC" => [
                                "descricao" => "Possui cartão de crédito contratado à pelo menos um ano.",
                                "valor"     => 100,
                                "risco"     => "ALTO",
                            ],
                            "comprometimentoRenda" => [
                                "descricao" => "O indicador possui percentual de 0%, pois recentemente esse titular não comprometeu tanto a sua renda com produtos de crédito.",
                                "valor"     => 0,
                                "risco"     => "BAIXO",
                            ],
                        ],
                    ],
                    "protestosPublicos" => [
                        "erroProtestos"    => false,
                        "protestoCompleto" => true,
                        "list"             => [
                            [
                                "uf"       => "SP",
                                "cidade"   => "CAMPINAS",
                                "data"     => "22/09/2017",
                                "valor"    => 164075.63,
                                "cartorio" => "2. TABELIAO DE PROTESTO DE LETRAS E TITULOS",
                            ],
                        ],
                        "qtdProtestos"     => 1,
                        "ultimaOcorrencia" => "19/02/2018",
                        "valorTotal"       => 4662462.95,
                    ],
                    "registrosDebitos" => null,
                    "ultimasConsultas" => null,
                    "rendaPresumida"   => [
                        "valor" => 3000,
                    ],
                    "cheques" => [],
                    "acoes"   => [],
                ],
            ];
        }

        if ($document === '11125897000199') {
            $return = [
                "cabecalho" => [
                    "entrada" => [
                        "documento" => "11.125.897/0001-99",
                        "acoes"     => false,
                    ],
                ],
                "dataHora"       => now()->format('d/m/Y H:i:s'),
                "produto"        => "Score de Crédito Restritivo",
                "funcionalidade" => "Score Completo Sem Ações - Pessoa Jurídica",
                "protocolo"      => "10d0ea1d-f808-2268-6688-f3c25a5be000",
                "reconsulta"     => false,
                "resposta"       => [
                    "score" => [
                        "classe" => "F",
                        "faixa"  => [
                            "titulo"    => "Altíssimo risco",
                            "descricao" => "Consumidores com essa classificação de score apresentam 15% de chances de honrar seus compromissos nos próximos 6 meses.",
                        ],
                        "pontos" => 0,
                    ],
                    "protestosPublicos" => [
                        "erroProtestos"    => false,
                        "protestoCompleto" => true,
                        "list"             => [
                            [
                                "uf"       => "SP",
                                "cidade"   => "CAMPINAS",
                                "data"     => "22/09/2017",
                                "valor"    => 164075.63,
                                "cartorio" => "2. TABELIAO DE PROTESTO DE LETRAS E TITULOS",
                            ],
                        ],
                        "qtdProtestos"     => 1,
                        "ultimaOcorrencia" => "19/02/2018",
                        "valorTotal"       => 4662462.95,
                    ],
                    "faturamentoEstimado" => [
                        "valor" => 750000000,
                    ],
                    "registrosDebitos" => null,
                    "ultimasConsultas" => [
                        "list" => [
                            [
                                "consultante"    => "SPC/ESTIVAL",
                                "dataOcorrencia" => "30/05/2022",
                            ],
                        ],
                        "qtdUltConsultas"  => 96,
                        "ultimaOcorrencia" => "30/05/2022",
                    ],
                    "acoes"   => [],
                    "cheques" => [],
                ],
            ];
        }

        try {
            return $this->insertResponseReturnInTable($return, $document, $tipo_consulta);
        } catch (Exception $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                400
            );
        }
    }

    private function insertResponseReturnInTable(array $returnResponse, string $document, string $tipo_consulta)
    {
        $dataHora = explode(' ', (string) $returnResponse['dataHora']);
        $data     = Carbon::createFromFormat('d/m/Y', $dataHora[0]);
        $hora     = Carbon::rawCreateFromFormat('H:i:s', $dataHora[1]);

        try {
            $response = ScoreResponse::create([
                'PESSOA_DOC'            => $document,
                'DATA'                  => $data->format('Y-m-d'),
                'HORA'                  => $hora->format('H:i:s'),
                'PRODUTO'               => mb_convert_encoding((string) $returnResponse['produto'], 'ISO-8859-1'),
                'FUNCIONALIDADE'        => mb_convert_encoding((string) $returnResponse['funcionalidade'], 'ISO-8859-1'),
                'PROTOCOLO'             => $returnResponse['protocolo'],
                'SCORE_CLASSE'          => $returnResponse['resposta']['score']['classe'],
                'SCORE_FAIXA_TITULO'    => mb_convert_encoding((string) $returnResponse['resposta']['score']['faixa']['titulo'], 'ISO-8859-1'),
                'SCORE_FAIXA_DESCRICAO' => mb_convert_encoding((string) $returnResponse['resposta']['score']['faixa']['descricao'], 'ISO-8859-1'),
                'SCORE_PONTOS'          => $returnResponse['resposta']['score']['pontos'],
                'RENDA_PRESUMIDA'       => $returnResponse['resposta']['rendaPresumida']['valor'] ?? 0,
                'EXPIRA_EM'             => $data->addDays(7)->format('Y-m-d'),
                'TIPO_CONSULTA'         => $tipo_consulta,
                'FATURAMENTO_ESTIMADO'  => $returnResponse['resposta']['faturamentoEstimado']['valor']
                             ?? 0,
                'ACOES_ULT_OCORRENCIA' => $returnResponse['resposta']['acoes']['ultimaOcorrencia']
                                            ?? null,
                'ACOES_VALOR_TOTAL' => $returnResponse['resposta']['acoes']['valorTotal']
                                            ?? null,
                'ACOES_QTD' => $returnResponse['resposta']['acoes']['qtdAcoes']
                                            ?? null,
            ]);

            return new AssertivaResource($response);
        } catch (Exception $e) {
            return throw new Exception('Erro ao criar registro na tabela: ASSERTIVA_SCORE_RETORNO: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
