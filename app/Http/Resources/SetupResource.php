<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RealEstateSectorSetup
 * @property \App\Models\RealEstateSectorSetup $resource
 */
class SetupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->ID,
            'taxa' => $this->TAXA !== null
                ? number_format(floatval($this->TAXA), 2, ',', '') . '%'
                : null,

            'ativo' => $this->ATIVO,
        ];
    }
}
