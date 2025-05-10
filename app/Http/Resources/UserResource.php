<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 * @property \App\Models\User $resource
 */
class UserResource extends JsonResource
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
            'usuario'        => $this->toUtf8($this->USUARIO),
            'nome'           => $this->toUtf8($this->NOME),
            'email'          => $this->toUtf8($this->EMAIL),
            'cpf'            => $this->formatCpf($this->CPF),
            'telefone'       => $this->formatPhone($this->TELEFONE),
            'nivel'          => $this->toUtf8($this->NIVEL),
            'categoria'      => $this->toUtf8($this->CATEGORIA),
            'id_imobiliaria' => $this->ID_IMOBILIARIA,
            'ativo'          => $this->ATIVO,
            'permissoes'     => $this->toUtf8($this->PERMISSOES),
        ];
    }
}
