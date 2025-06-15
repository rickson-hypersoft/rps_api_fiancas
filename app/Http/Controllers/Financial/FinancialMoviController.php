<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\FinancialMoviResource;
use App\Models\FinancialMovi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialMoviController extends Controller
{
    public function index(Request $request, string | int $id): JsonResponse
    {
        $financialMovi = FinancialMovi::where('ID_IMOBILIARIA', '=', $id)->get();

        return response()->json(['data' => FinancialMoviResource::collection($financialMovi)]);
    }

    public function find(string | int $id): JsonResponse
    {
        $financialMovi = FinancialMovi::query()->where("ID", "=", $id)->firstOrFail();

        return response()->json(['data' => $financialMovi]);
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
            'id_conta'       => 'required|numeric',
            'id_categoria'   => 'required|numeric',
            'data'           => 'nullable|date',
            'historico'      => 'nullable|string|max:100',
            'tipo'           => 'nullable|string|max:1',
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
}
