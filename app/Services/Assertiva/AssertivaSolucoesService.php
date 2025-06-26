<?php

declare(strict_types = 1);

namespace App\Services\Assertiva;

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

        /*
        $url  = "{$this->baseUrl}/{$type}/credito/{$document}";

        $query = ['idFinalidade' => $finaly];

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->get($url, $query);

        if ($response->failed()) {
            throw new Exception('Erro ao consulta Score Assertiva: ' . $response->body());
        }

        return $response->json();
        */

        if ($document === '16054956620') {
            return response()->json([
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
                ],
            ]);
        }

        if ($document === '50384631002') {
            return response()->json([
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
            ]);
        }

        if ($document === '51907352066') {
            return response()->json([
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
            ]);
        }

        return response()->json([
            'score'    => null,
            'mensagem' => 'Documento não encontrado no mock',
        ], 404);
    }
}
