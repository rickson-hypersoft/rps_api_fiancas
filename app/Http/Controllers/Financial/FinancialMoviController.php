<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\FinancialMoviResource;
use App\Models\FinancialMovi;
use App\Services\Financial\FinancialMoviService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FinancialMoviController extends Controller
{
    public function index(Request $request, string | int $id, FinancialMoviService $service): JsonResponse
    {
        $dataHoje = date('Y-m-d');

        // Montagem da query de movimentações (igual estava)
        $query = DB::table('FINANCEIRO_MOVI')->where('ID_IMOBILIARIA', $id);

        // Aplicar filtros igual antes...
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
            $search    = $request->input('descricao');
            $searchIso = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $search);

            $contaIds = DB::table('FINANCEIRO_CONTAS')
                ->whereRaw('UPPER(DESCRICAO) LIKE UPPER(?)', ["%$searchIso%"])
                ->pluck('ID');

            if ($contaIds->isNotEmpty()) {
                $query->whereIn('ID_CONTA', $contaIds);
            } else {
                $query->whereRaw('0=1');
            }
        }

        $movimentacoes = $query->orderBy('ID', 'desc')->paginate(10);

        // ✅ Nova forma de buscar os totais de uma vez
        $totais = $service->calculateTotal(
            $id,
            $request->input('data_inicial', $dataHoje),
            $request->input('data_final', $dataHoje),
            $request->input('id_conta'),
            $request->input('id_categoria'),
        );

        return response()->json([
            'data' => FinancialMoviResource::collection($movimentacoes),
            'meta' => [
                'current_page' => $movimentacoes->currentPage(),
                'from'         => $movimentacoes->firstItem(),
                'last_page'    => $movimentacoes->lastPage(),
                'links'        => $movimentacoes->linkCollection(),
                'path'         => $request->url(),
                'per_page'     => $movimentacoes->perPage(),
                'to'           => $movimentacoes->lastItem(),
                'total'        => $movimentacoes->total(),
            ],
            'valores' => [
                'saldoAnterior' => $totais['saldoAnterior'] ?? 0,
                'entradas'      => $totais['creditos'] ?? 0,
                'saidas'        => $totais['debitos'] ?? 0,
                'saldoAtual'    => ($totais['saldoAnterior'] ?? 0) + ($totais['creditos'] ?? 0) - ($totais['debitos'] ?? 0),
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
            'valor'          => 'nullable|numeric',
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
            'valor'          => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $financialMoviData          = $validator->validated();
        $financialMoviData['valor'] = floatval($financialMoviData['valor'] ?? 0);
        $financialMoviData          = $this->convertIsoAndTransformUpperCase($financialMoviData);

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
