<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->ID, // Firebird usa letras maiúsculas
            'usuario'          => $this->USUARIO,
            'nome'             => $this->NOME,
            'email'            => $this->EMAIL,
            'cpf'              => $this->formatCpf($this->CPF),
            'telefone'         => $this->formatPhone($this->TELEFONE),
            'nivel'            => $this->NIVEL,
            'categoria'        => $this->CATEGORIA,
            'id_imobibiliaria' => $this->id_imobibiliaria,
            'ativo'            => $this->ATIVO,
            'permissoes'       => $this->PERMISSOES,
        ];
    }

    private function formatCpf(?string $cpf): ?string
    {
        if (! $cpf || strlen($cpf) !== 11) {
            return $cpf;
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    private function formatPhone(?string $phone): ?string
    {
        if (! $phone) {
            return $phone;
        }

        // Remove tudo que não for número
        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
        }

        if (strlen($phone) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
        }

        return $phone;
    }
}
