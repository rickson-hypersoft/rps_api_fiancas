<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Company
 * @property \App\Models\Attachament $resource
 */
class AttachamentResource extends JsonResource
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
            'id_imobiliaria'        => $this->ID_IMOBILIARIA,
            'id_movi'               => $this->ID_MOVI,
            'movi_sub'              => ucfirst((string) $this->toUtf8($this->MOVI_SUB)),
            'data'                  => $this->DATA,
            'nome_arquivo'          => $this->toUtf8($this->NOME_ARQUIVO),
            'descricao'             => $this->toUtf8($this->DESCRICAO),
            'nome_arquivo_original' => $this->toUtf8($this->NOME_ARQUIVO_ORIGINAL),
            'movi'                  => ucfirst((string) $this->toUtf8($this->MOVI)),
        ];
    }
}
