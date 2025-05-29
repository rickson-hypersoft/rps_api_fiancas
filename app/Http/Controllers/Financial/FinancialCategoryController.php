<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\FinancialCategoryResource;
use App\Models\FinancialCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialCategoryController extends Controller
{
    public function index(Request $request, string | int $id): JsonResponse
    {
        $perPage           = $request->get('per_page', 5);
        $financialCategory = FinancialCategory::where('ID_IMOBILIARIA', $id)->paginate($perPage);

        return FinancialCategoryResource::collection($financialCategory)->response()->setStatusCode(200);
    }

    public function find(string | int $id): JsonResponse
    {
        $financialCategory = FinancialCategory::query()->where("ID", "=", $id)->firstOrFail();
        $financialCategory = new FinancialCategoryResource($financialCategory);

        return response()->json(['data' => $financialCategory]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_imobiliaria' => 'required|numeric',
            'descricao'      => 'nullable|string|max:100',
            'sistema'        => 'nullable|numeric|between:0,1',
            'tipo'           => 'nullable|string|max:1',
            'ativo'          => 'nullable|numeric|between:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $financialCategoryData = $validator->validated();
        $financialCategoryData = $this->convertIsoAndTransformUpperCase($financialCategoryData);

        $financialCategory = FinancialCategory::create($financialCategoryData);

        return response()->json([
            "success" => true,
            "message" => "Categoria financeira criada com sucesso!",
            "data"    => new FinancialCategoryResource($financialCategory),
        ], 201);
    }

    public function update(int | string $id, Request $request): JsonResponse
    {
        $financialCategory = FinancialCategory::query()->where("ID", "=", $id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'id_imobiliaria' => 'nullable|numeric',
            'descricao'      => 'nullable|string|max:100',
            'sistema'        => 'nullable|numeric|between:0,1',
            'tipo'           => 'nullable|string|max:1',
            'ativo'          => 'nullable|numeric|between:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $financialCategoryData = $validator->validated();
        $financialCategoryData = $this->convertIsoAndTransformUpperCase($financialCategoryData);

        $financialCategory->update($financialCategoryData);

        return response()->json([
            "success" => true,
            "message" => "Categoria financeira atualizada com sucesso!",
        ], 200);
    }
}
