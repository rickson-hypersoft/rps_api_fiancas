<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoryResource extends JsonResource
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
            'id_movi'        => $this->ID_MOVI,
            'movi'           => $this->toUtf8($this->MOVI),
            'data'           => $this->DATA,
            'hora'           => $this->HORA,
            'historico'      => $this->toUtf8($this->HISTORICO),
            'id_usuario'     => $this->ID_USUARIO,
            'usuario'        => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
