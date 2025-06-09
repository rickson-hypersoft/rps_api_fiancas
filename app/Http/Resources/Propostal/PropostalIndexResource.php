<?php

declare(strict_types = 1);

namespace App\Http\Resources\Propostal;

use App\Http\Resources\ResourceTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Propostal\Propostal
 * @property \App\Models\Propostal\Propostal $resource
 */
class PropostalIndexResource extends JsonResource
{
    use ResourceTrait;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $valorTotalPagamento = $this->PROPOSTA_TOTAL_VALOR + $this->PROPOSTA_SETUP_VALOR;

        return [

            'id'                      => $this->ID,
            'id_imobiliaria'          => $this->ID_IMOBILIARIA,
            'data'                    => $this->DATA,
            'hora'                    => $this->HORA,
            'data_ultima_atualizacao' => $this->DATA_ULTIMA_ATUALIZACAO,
            'hora_ultima_atualizacao' => $this->HORA_ULTIMA_ATUALIZACAO,
            'pessoa_tipo'             => match ($this->PESSOA_TIPO) {
                'pf'    => 'Pessoa Física',
                'pj'    => 'Pessoa Jurídica',
                default => 'Tipo Desconhecido',
            },
            'pessoa_doc'        => $this->formatCpfCnpj($this->PESSOA_DOC),
            'pessoa_nome'       => $this->toUtf8($this->PESSOA_NOME),
            'pessoa_fantasia'   => $this->toUtf8($this->PESSOA_FANTASIA),
            'pessoa_tributacao' => $this->toUtf8($this->PESSOA_TRIBUTACAO),
            'pessoa_pj_tipo'    => $this->toUtf8($this->PESSOA_PJ_TIPO),
            'pessoa_email'      => $this->toUtf8($this->PESSOA_EMAIL),
            'pessoa_telefone'   => $this->toUtf8($this->PESSOA_TELEFONE),
            'pessoa_cep'        => $this->formatZipCode($this->PESSOA_CEP),
            'pessoa_endereco'   => $this->toUtf8($this->PESSOA_ENDERECO),
            'pessoa_bairro'     => $this->toUtf8($this->PESSOA_BAIRRO),
            'pessoa_cidade'     => $this->toUtf8($this->PESSOA_CIDADE),
            'pessoa_estado'     => $this->toUtf8($this->PESSOA_ESTADO),
            'pessoa_numero'     => $this->toUtf8($this->PESSOA_NUMERO),
            'imovel_tipo'       => match ($this->IMOVEL_TIPO) {
                'R'     => 'Residencial',
                'C'     => 'Comercial',
                default => 'Tipo Desconhecido',
            },
            'imovel_aluguel' => $this->IMOVEL_ALUGUEL !== null
                ? 'R$ ' . number_format(floatval($this->IMOVEL_ALUGUEL, ), 2, ',', '')
                : null,
            'imovel_condominio' => $this->IMOVEL_CONDOMINIO !== null
                ? 'R$ ' . number_format(floatval($this->IMOVEL_CONDOMINIO, ), 2, ',', '')
                : null,
            'imovel_taxas' => $this->IMOVEL_TAXAS !== null
                ? 'R$ ' . number_format(floatval($this->IMOVEL_TAXAS, ), 2, ',', '')
                : null,
            'imovel_cep'           => $this->formatZipCode($this->IMOVEL_CEP),
            'imovel_endereco'      => $this->toUtf8($this->IMOVEL_ENDERECO),
            'imovel_bairro'        => $this->toUtf8($this->IMOVEL_BAIRRO),
            'imovel_cidade'        => $this->toUtf8($this->IMOVEL_CIDADE),
            'imovel_estado'        => $this->toUtf8($this->IMOVEL_ESTADO),
            'imovel_numero'        => $this->toUtf8($this->IMOVEL_NUMERO),
            'imovel_complemento'   => $this->toUtf8($this->IMOVEL_COMPLEMENTO),
            'imovel_subtipo'       => $this->toUtf8($this->IMOVEL_SUBTIPO),
            'imovel_tag'           => $this->toUtf8($this->IMOVEL_TAG),
            'imovel_ramo_atv'      => $this->toUtf8($this->IMOVEL_RAMO_ATV),
            'proposta_total_valor' => $this->PROPOSTA_TOTAL_VALOR !== null
                ? 'R$ ' . number_format(floatval($this->PROPOSTA_TOTAL_VALOR, ), 2, ',', '')
                : null,
            'proposta_total_parc'  => $this->PROPOSTA_TOTAL_PARC,
            'proposta_setup_valor' => $this->PROPOSTA_SETUP_VALOR !== null
                ? 'R$ ' . number_format(floatval($this->PROPOSTA_SETUP_VALOR, ), 2, ',', '')
                : null,
            'proposta_setup_parc'     => $this->PROPOSTA_SETUP_PARC,
            'proposta_tipo_pagador'   => $this->toUtf8($this->PROPOSTA_TIPO_PAGADOR),
            'proposta_status'         => $this->toUtf8($this->PROPOSTA_STATUS),
            'contrato_id'             => $this->CONTRATO_ID,
            'proposta_credito_status' => $this->PROPOSTA_CREDITO_STATUS,
            'data_nascimento'         => $this->DATA_NASCIMENTO,
            'contrato_status'         => $this->toUtf8($this->CONTRATO_STATUS),
            'observacao'              => $this->toUtf8($this->OBSERVACAO),
            'endereco_completo'       => "{$this->toUtf8($this->IMOVEL_ENDERECO)}, {$this->toUtf8($this->IMOVEL_NUMERO)}, {$this->toUtf8($this->IMOVEL_BAIRRO)}, {$this->toUtf8($this->IMOVEL_CIDADE)} - {$this->toUtf8($this->IMOVEL_ESTADO)}",
            'link_hash'               => $this->LINK_HASH,
            'valor_total_pagamento'   => $valorTotalPagamento !== null
                ? 'R$ ' . number_format(floatval($valorTotalPagamento, ), 2, ',', '')
                : null,
        ];
    }
}
