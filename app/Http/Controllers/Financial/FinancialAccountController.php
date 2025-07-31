<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\FinancialAccountResource;
use App\Models\FinancialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialAccountController extends Controller
{
    public function index(Request $request, string | int $id): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $query   = FinancialAccount::where('ID_IMOBILIARIA', $id);

        if ($request->filled('search')) {
            $search    = $request->input('search');
            $searchIso = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string) $search);
            $query->whereRaw('UPPER(DESCRICAO) LIKE UPPER(?)', ["%$searchIso%"]);
        }

        if ($request->filled('active')) {
            $query->where('ATIVO', '=', 1);
        }

        $financialAccounts = $query->orderBy('DESCRICAO', 'asc')
            ->paginate($perPage);

        return FinancialAccountResource::collection($financialAccounts)
            ->response()
            ->setStatusCode(200);
    }

    public function find(string | int $id): JsonResponse
    {
        $financialAccount = FinancialAccount::query()->where("ID", "=", $id)->firstOrFail();
        $financialAccount = new FinancialAccountResource($financialAccount);

        return response()->json(['data' => $financialAccount]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_imobiliaria'   => 'required|numeric',
            'tipo_conta'       => 'nullable|string|max:50',
            'descricao'        => 'nullable|string|max:100',
            'banco_titular'    => 'nullable|string|max:100',
            'banco_cnpj'       => 'nullable|string|max:100',
            'banco'            => 'nullable|string|max:3',
            'banco_agencia'    => 'nullable|string|max:100',
            'banco_conta'      => 'nullable|max:100',
            'banco_finalidade' => 'nullable|string|max:100',
            'banco_pix'        => 'nullable|string|max:100',
            'ativo'            => 'nullable|numeric|between:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $financialAccountData = $validator->validated();
        $financialAccountData = $this->convertIsoAndTransformUpperCase($financialAccountData);

        try {
            $financialAccount = FinancialAccount::create($financialAccountData);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Erro ao criar conta financeira: " . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            "success" => true,
            "message" => "Conta financeira criada com sucesso!",
            "data"    => new FinancialAccountResource($financialAccount),
        ], 201);
    }

    public function update(int | string $id, Request $request): JsonResponse
    {
        $financialAccount = FinancialAccount::findOrFail($id);

        // Use o helper validate para simplificar
        $data = $request->validate([
            'id_imobiliaria'   => 'required|numeric',
            'tipo_conta'       => 'nullable|string|max:50',
            'descricao'        => 'nullable|string|max:100',
            'banco_titular'    => 'nullable|string|max:100',
            'banco_cnpj'       => 'nullable|string|max:100',
            'banco'            => 'nullable|string|max:3',
            'banco_agencia'    => 'nullable|string|max:100',
            'banco_conta'      => 'nullable|string|max:100',
            'banco_finalidade' => 'nullable|string|max:100',
            'banco_pix'        => 'nullable|string|max:100',
            'ativo'            => 'nullable|numeric|between:0,1',
        ]);

        // (opcional) transforme campos aqui
        $data = $this->convertIsoAndTransformUpperCase($data);

        $financialAccount->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Conta financeira atualizada com sucesso!',
        ], 200);
    }

    public function destroy(int | string $id): JsonResponse
    {
        $financialAccount = FinancialAccount::query()->where("ID", "=", $id)->first();

        if (! $financialAccount) {
            return response()->json([
                "success" => false,
                "message" => "Conta financeira não encontrada.",
            ], 404);
        }

        $financialAccount->delete();

        return response()->json([
            "success" => true,
            "message" => "Conta financeira deletada com sucesso.",
        ], 200);
    }
}
