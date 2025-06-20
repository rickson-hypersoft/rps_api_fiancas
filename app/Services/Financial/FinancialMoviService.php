<?php

declare(strict_types = 1);

namespace App\Services\Financial;

use Illuminate\Support\Facades\DB;

class FinancialMoviService
{
    public function calculateTotal(int | string $idImobiliaria, ?string $dataInicial, ?string $dataFinal, ?string $idConta, ?string $idCategoria): array
    {
        $bindings = [
            'id1'    => $idImobiliaria,
            'datai1' => $dataInicial ?? date('Y-m-d'),

            'id2'    => $idImobiliaria,
            'datai2' => $dataInicial ?? date('Y-m-d'),
            'dataf2' => $dataFinal ?? date('Y-m-d'),

            'id3'    => $idImobiliaria,
            'datai3' => $dataInicial ?? date('Y-m-d'),
            'dataf3' => $dataFinal ?? date('Y-m-d'),
        ];

        // ===== WHERE para cada bloco =====
        $whereConta1 = '';
        $whereConta2 = '';
        $whereConta3 = '';

        $whereCategoria1 = '';
        $whereCategoria2 = '';
        $whereCategoria3 = '';

        if ($idConta !== null && $idConta !== '') {
            $whereConta1 = ' AND id_conta = :idConta1';
            $whereConta2 = ' AND id_conta = :idConta2';
            $whereConta3 = ' AND id_conta = :idConta3';

            $bindings['idConta1'] = $idConta;
            $bindings['idConta2'] = $idConta;
            $bindings['idConta3'] = $idConta;
        }

        if ($idCategoria !== null && $idCategoria !== '') {
            $whereCategoria1 = ' AND id_categoria = :idCategoria1';
            $whereCategoria2 = ' AND id_categoria = :idCategoria2';
            $whereCategoria3 = ' AND id_categoria = :idCategoria3';

            $bindings['idCategoria1'] = $idCategoria;
            $bindings['idCategoria2'] = $idCategoria;
            $bindings['idCategoria3'] = $idCategoria;
        }

        // ===== SQL FINAL =====
        $sql = "
            SELECT 'saldoAnterior' AS descricao, SUM(CASE WHEN tipo = 'C' THEN valor ELSE valor * -1 END) AS total
            FROM financeiro_movi
            WHERE id_imobiliaria = :id1 AND data < :datai1
            $whereConta1
            $whereCategoria1

            UNION ALL

            SELECT 'creditos' AS descricao, SUM(valor) AS total
            FROM financeiro_movi
            WHERE id_imobiliaria = :id2 AND data >= :datai2 AND data <= :dataf2 AND tipo = 'C'
            $whereConta2
            $whereCategoria2

            UNION ALL

            SELECT 'debitos' AS descricao, SUM(valor) AS total
            FROM financeiro_movi
            WHERE id_imobiliaria = :id3 AND data >= :datai3 AND data <= :dataf3 AND tipo = 'D'
            $whereConta3
            $whereCategoria3
        ";

        $result = DB::select($sql, $bindings);

        return collect($result)->mapWithKeys(function ($item) {
            return [trim($item->DESCRICAO) => (float) $item->TOTAL];
        })->all();
    }
}
