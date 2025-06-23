<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Company
 * @property \App\Models\Company $resource
 */
class CompanyResource extends JsonResource
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
            'id'            => $this->ID,
            'razao'         => $this->toUtf8($this->RAZAO),
            'fantasia'      => $this->toUtf8($this->FANTASIA),
            'cnpj'          => $this->formatCnpj($this->CNPJ),
            'endereco'      => $this->toUtf8($this->ENDERECO),
            'numero'        => $this->toUtf8($this->NUMERO),
            'bairro'        => $this->toUtf8($this->BAIRRO),
            'cidade'        => $this->toUtf8($this->CIDADE),
            'uf'            => $this->toUtf8($this->UF),
            'cep'           => $this->formatZipCode($this->CEP),
            'complemento'   => $this->toUtf8($this->COMPLEMENTO),
            'telefone'      => $this->formatPhone($this->TELEFONE),
            'contato'       => $this->toUtf8($this->CONTATO),
            'cargo'         => $this->toUtf8($this->CARGO),
            'representante' => $this->toUtf8($this->REPRESENTANTE),
            'email'         => $this->toUtf8($this->EMAIL),
        ];
    }
}