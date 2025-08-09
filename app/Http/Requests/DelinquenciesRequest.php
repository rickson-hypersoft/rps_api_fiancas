<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DelinquenciesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contrato_id'         => 'required|numeric',
            'id_imobiliaria'      => 'required|numeric',
            'imovel_situacao'     => 'nullable|string|max:50',
            'tipo_conta'          => 'nullable|string|max:50',
            'valor_original'      => 'nullable|numeric',
            'vencimento_original' => 'nullable|date',
            'conta_bancaria_id'   => 'nullable|numeric',
            'observacao'          => 'nullable|string|max:100',
            'status'              => 'nullable|string|max:50',
            'data_criacao'        => 'nullable|date',
            'hora_criacao'        => 'nullable|string|max:10',
            'data_pagamento'      => 'nullable|date',
            'hora_pagamento'      => 'nullable|string|max:10',
            'tipo_inadimplencia'  => 'nullable|string|max:50',
            'valor_aprovado'      => 'nullable|numeric',
        ];
    }
}
