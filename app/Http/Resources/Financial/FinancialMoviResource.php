<?php

declare(strict_types = 1);

namespace App\Http\Resources\Financial;

use App\Http\Resources\ResourceTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\FinancialMovi
 * @property \App\Models\FinancialMovi $resource
 */
class FinancialMoviResource extends JsonResource
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
            'id_imobiliaria' => $this->ID_IMOBILIARIA,
            'id_conta'       => $this->ID_CONTA,
            'id_categoria'   => $this->ID_CATEGORIA,
            'historico'      => $this->HISTORICO ? utf8_encode($this->HISTORICO) : null,
            'valor'          => $this->VALOR,
            'data'           => $this->DATA,
            'tipo'           => match ($this->TIPO) {
                'C'     => 'Crédito',
                'D'     => 'Débito',
                'E'     => 'Escolher',
                default => 'Desconhecido',
            },
        ];
    }
}
