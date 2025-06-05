<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RealEstateSector
 * @property \App\Models\RealEstateSector $resource
 * @property-read \App\Models\Setup|null $setup
 */
class RealEstateSectorResource extends JsonResource
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
            'id'             => $this->ID,
            'razao'          => $this->toUtf8($this->RAZAO),
            'fantasia'       => $this->toUtf8($this->FANTASIA),
            'creci'          => $this->toUtf8($this->CRECI),
            'cnpj'           => $this->formatCnpj($this->CNPJ),
            'endereco'       => $this->toUtf8($this->ENDERECO),
            'numero'         => $this->toUtf8($this->NUMERO),
            'bairro'         => $this->toUtf8($this->BAIRRO),
            'cidade'         => $this->toUtf8($this->CIDADE),
            'uf'             => $this->toUtf8($this->UF),
            'cep'            => $this->formatZipCode($this->CEP),
            'complemento'    => $this->toUtf8($this->COMPLEMENTO),
            'telefone'       => $this->formatPhone($this->TELEFONE),
            'contato'        => $this->toUtf8($this->CONTATO),
            'cargo'          => $this->toUtf8($this->CARGO),
            'representante'  => $this->toUtf8($this->REPRESENTANTE),
            'email'          => $this->toUtf8($this->EMAIL),
            'tipo_pagamento' => $this->toUtf8($this->TIPO_PAGAMENTO),
            'taxa_padrao'    => $this->TAXA_PADRAO !== null
                ? number_format(floatval($this->TAXA_PADRAO), 2, ',', '')
                : null,
            'custo_saida'     => $this->CUSTO_SAIDA,
            'cobertura_total' => $this->COBERTURA_TOTAL,
            'setup'           => SetupResource::collection($this->whenLoaded('setups')),
            'ativo'           => $this->ATIVO,
        ];
    }
}
