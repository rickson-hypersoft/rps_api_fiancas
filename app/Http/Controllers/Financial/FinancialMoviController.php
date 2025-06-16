<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\FinancialMoviResource;
use App\Models\FinancialMovi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FinancialMoviController extends Controller
{
    public function index(Request $request, string | int $id): JsonResponse
    {
        $dataHoje = date('Y-m-d');

        // Query de movimentações filtradas
        $query = DB::table('FINANCEIRO_MOVI')
            ->where('ID_IMOBILIARIA', $id);

        // Filtros opcionais
        if ($request->filled('id_categoria')) {
            $query->where('ID_CATEGORIA', $request->id_categoria);
        }

        if ($request->filled('id_conta')) {
            $query->where('ID_CONTA', $request->id_conta);
        }

        if ($request->filled('data_inicial')) {
            $query->where('DATA', '>=', $request->data_inicial);
        }

        if ($request->filled('data_final')) {
            $query->where('DATA', '<=', $request->data_final);
        }

        if ($request->filled('descricao')) {
            $descricao = mb_strtoupper($request->descricao, 'UTF-8');
            $query->whereRaw('UPPER(DESCRICAO) LIKE ?', ['%' . $descricao . '%']);
        }

        // Movimentações do período filtrado
        $movimentacoes = $query->get();

        // SALDO ANTERIOR
        $saldoAnteriorQuery = DB::table('FINANCEIRO_MOVI')
            ->where('ID_IMOBILIARIA', $id);

        if ($request->filled('data_inicial')) {
            $saldoAnteriorQuery->where('DATA', '<', $request->data_inicial);
        } else {
            $saldoAnteriorQuery->where('DATA', '<', $dataHoje);
        }

        $saldoAnterior = $saldoAnteriorQuery->get()->sum(function ($mov) {
            return $mov->TIPO === 'C' ? $mov->VALOR : -$mov->VALOR;
        });

        // Entradas e Saídas do período atual (apenas o período filtrado)
        $entradas = $movimentacoes->where('TIPO', 'C')->sum('VALOR');
        $saidas   = $movimentacoes->where('TIPO', 'D')->sum('VALOR');

        // Saldo do período (não incluir o saldo anterior!)
        $saldoPeriodo = $entradas - $saidas;

        return response()->json([
            'data'    => FinancialMoviResource::collection($movimentacoes),
            'valores' => [
                'saldoAnterior' => $saldoAnterior,
                'entradas'      => $entradas,
                'saidas'        => $saidas,
                'saldoAtual'    => $saldoPeriodo,  // <-- saldo só do período
            ],
        ]);
    }

    public function find(string | int $id): JsonResponse
    {
        $financialMovi = FinancialMovi::query()->where("ID", "=", $id)->firstOrFail();

        return response()->json(['data' => new FinancialMoviResource($financialMovi)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_imobiliaria' => 'required|numeric',
            'id_conta'       => 'nullable|numeric',
            'id_categoria'   => 'nullable|numeric',
            'data'           => 'nullable|date',
            'historico'      => 'nullable|string|max:100',
            'tipo'           => 'nullable|string|max:1',
            'valor'          => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $financialMoviData = $validator->validated();
        $financialMoviData = $this->convertIsoAndTransformUpperCase($financialMoviData);

        $financialMovi = FinancialMovi::create($financialMoviData);

        return response()->json([
            "success" => true,
            "message" => "Movimentação financeira criada com sucesso!",
            "data"    => new FinancialMoviResource($financialMovi),
        ], 201);
    }

    public function update(int | string $id, Request $request): JsonResponse
    {
        $financialMovi = FinancialMovi::query()->where("ID", "=", $id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'id_imobiliaria' => 'required|numeric',
            'id_conta'       => 'nullable|numeric',
            'id_categoria'   => 'nullable|numeric',
            'data'           => 'nullable|date',
            'historico'      => 'nullable|string|max:100',
            'tipo'           => 'nullable|string|max:1',
            'valor'          => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $financialMoviData = $validator->validated();
        $financialMoviData = $this->convertIsoAndTransformUpperCase($financialMoviData);

        $financialMovi->update($financialMoviData);

        return response()->json([
            "success" => true,
            "message" => "Movimentação financeira atualizada com sucesso!",
        ], 200);
    }

    public function destroy(int | string $id): JsonResponse
    {
        $financialMovi = FinancialMovi::query()->where("ID", "=", $id)->first();

        if (! $financialMovi) {
            return response()->json([
                "success" => false,
                "message" => "Categoria financeira não encontrada.",
            ], 404);
        }

        $financialMovi->delete();

        return response()->json([
            "success" => true,
            "message" => "Movimentação financeira deletada com sucesso.",
        ], 200);
    }
}
