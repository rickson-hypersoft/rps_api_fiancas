<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Deliquencies
 * @property \App\Models\Deliquencies $resource
 */
class DelinquenciesResourceAttachment extends JsonResource
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
            'id'                  => $this->ID,
            'id_imobiliaria'      => $this->ID_IMOBILIARIA,
            'contrato_id'         => $this->CONTRATO_ID,
            'imovel_situacao'     => $this->IMOVEL_SITUACAO,
            'valor_original'      => $this->VALOR_ORIGINAL,
            'vencimento_original' => $this->VENCIMENTO_ORIGINAL,
            'conta_bancaria_id'   => $this->CONTA_BANCARIA_ID,
            'observacao'          => $this->toUtf8($this->OBSERVACAO),
            'status'              => $this->toUtf8($this->STATUS),
            'data_criacao'        => $this->DATA_CRIACAO,
            'hora_criacao'        => $this->HORA_CRIACAO,
            'data_pagamento'      => $this->DATA_PAGAMENTO,
            'hora_pagamento'      => $this->HORA_PAGAMENTO,
            'tipo_inadimplencia'  => $this->TIPO_INADIMPLENCIA,
            'valor_aprovado'      => $this->VALOR_APROVADO,
            'forma_pagamento'     => $this->FORMA_PAGAMENTO,
            'tipo_conta'          => $this->toUtf8($this->TIPO_CONTA),
            'attachments'         => AttachamentResource::collection($this->whenLoaded('attachments')),
            'histories'           => HistoryResource::collection($this->whenLoaded('histories')),
        ];
    }
}
