<?php

declare(strict_types = 1);

namespace App\Http\Resources\Financial;

use App\Http\Resources\ResourceTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RealEstateSector
 * @property \App\Models\RealEstateSector $resource
 * @property-read \App\Models\Setup|null $setup
 */
class FinancialAccountResource extends JsonResource
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
            'id'               => $this->ID,
            'id_imobiliaria'   => $this->ID_IMOBILIARIA,
            'tipo_conta'       => $this->toUtf8($this->TIPO_CONTA),
            'descricao'        => $this->toUtf8($this->DESCRICAO),
            'banco_titular'    => $this->toUtf8($this->BANCO_TITULAR),
            'banco_cnpj'       => $this->formatCnpj($this->BANCO_CNPJ),
            'banco'            => $this->toUtf8($this->BANCO),
            'banco_agencia'    => $this->toUtf8($this->BANCO_AGENCIA),
            'banco_conta'      => $this->toUtf8($this->BANCO_CONTA),
            'banco_finalidade' => $this->toUtf8($this->BANCO_FINALIDADE),
            'banco_pix'        => $this->toUtf8($this->BANCO_PIX),
            'ativo'            => $this->ATIVO,
        ];
    }
}
