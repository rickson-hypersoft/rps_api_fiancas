<?php

declare(strict_types = 1);

namespace App\Http\Resources\Assertiva;

use App\Http\Resources\ResourceTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ScoreResponse
 * @property \App\Models\ScoreResponse $resource
 */
class AssertivaResource extends JsonResource
{
    use ResourceTrait;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->ID,
            'pessoa_doc'            => $this->PESSOA_DOC,
            'data'                  => $this->DATA,
            'hora'                  => $this->HORA,
            'produto'               => $this->toUtf8($this->PRODUTO),
            'funcionalidade'        => $this->toUtf8($this->FUNCIONALIDADE),
            'protocolo'             => $this->PROTOCOLO,
            'score_classe'          => $this->SCORE_CLASSE,
            'score_faixa_titulo'    => $this->toUtf8($this->SCORE_FAIXA_TITULO),
            'score_faixa_descricao' => $this->toUtf8($this->SCORE_FAIXA_DESCRICAO),
            'score_pontos'          => $this->SCORE_PONTOS,
            'renda_presumida'       => $this->RENDA_PRESUMIDA,
            'expira_em'             => $this->EXPIRA_EM,
        ];
    }
}
