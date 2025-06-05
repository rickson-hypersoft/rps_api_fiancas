<?php

declare(strict_types=1);

namespace App\Http\Resources\Financial;

use App\Http\Resources\ResourceTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\FinancialCategory
 * @property \App\Models\FinancialCategory $resource
 */
class FinancialCategoryResource extends JsonResource
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
            'descricao'      => $this->toUtf8($this->DESCRICAO),
            'sistema'        => $this->SISTEMA,
            'tipo'           => match ($this->TIPO) {
                'C' => 'Crédito',
                'D' => 'Débito',
                'E' => 'Escolher',
                default => 'Desconhecido',
            },
            'ativo' => $this->ATIVO,
        ];
    }
}
