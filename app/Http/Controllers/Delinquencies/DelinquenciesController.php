<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Delinquencies;

use App\Http\Controllers\Controller;
use App\Http\Requests\DelinquenciesRequest;
use App\Http\Resources\DelinquenciesResource;
use App\Models\Delinquencies;
use App\Models\Propostal\Propostal;
use Illuminate\Http\Request;

class DelinquenciesController extends Controller
{
    public function index(Request $request, string | int $idImobiliaria)
    {
        $request->all();

        $delinquencies = Delinquencies::where('ID_IMOBILIARIA', '=', $idImobiliaria)
            ->get();

        return response()->json(
            DelinquenciesResource::collection($delinquencies)
        );
    }

    public function store(DelinquenciesRequest $request)
    {
        $delinquenciesData = $this->convertIsoAndTransformUpperCase($request->validated());
        $delinquencies     = Delinquencies::create($delinquenciesData);

        return response()->json(
            new DelinquenciesResource($delinquencies),
            201
        );
    }

    public function update(DelinquenciesRequest $request, int | string $id)
    {
        $delinquencies = Delinquencies::findOrFail($id);

        $delinquencies->update($request->validated());

        return response()->json(
            new DelinquenciesResource($delinquencies),
            200
        );
    }

    public function show(int | string $idImobiliaria, int | string $id)
    {
        $delinquencies = Delinquencies::findOrFail($id);

        Propostal::where('ID', $delinquencies->CONTRADO_ID)
            ->first();

        return response()->json(
            new DelinquenciesResource($delinquencies)
        );
    }

    public function destroy(int | string $id)
    {
        $delinquencies = Delinquencies::findOrFail($id);
        $delinquencies->delete();

        return response()->json(null, 204);
    }
}
