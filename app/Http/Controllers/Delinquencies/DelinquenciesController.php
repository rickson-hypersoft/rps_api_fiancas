<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Delinquencies;

use App\Http\Controllers\Controller;
use App\Http\Requests\DelinquenciesRequest;
use App\Http\Resources\DelinquenciesResource;
use App\Models\Deliquencies;
use Illuminate\Http\Request;

class DelinquenciesController extends Controller
{
    public function index(Request $request)
    {
        $request->all();

        $deliquencies = Deliquencies::query();

        return response()->json(
            DelinquenciesResource::collection($deliquencies)
        );
    }

    public function store(DelinquenciesRequest $request)
    {
        $deliquencies = Deliquencies::create($request->validated());

        return response()->json(
            new DelinquenciesResource($deliquencies),
            201
        );
    }

    public function update(DelinquenciesRequest $request, int | string $id)
    {
        $deliquencies = Deliquencies::findOrFail($id);

        $deliquencies->update($request->validated());

        return response()->json(
            new DelinquenciesResource($deliquencies),
            200
        );
    }

    public function show(int | string $id)
    {
        $deliquencies = Deliquencies::findOrFail($id);

        return response()->json(
            new DelinquenciesResource($deliquencies)
        );
    }

    public function destroy(int | string $id)
    {
        $deliquencies = Deliquencies::findOrFail($id);
        $deliquencies->delete();

        return response()->json(null, 204);
    }
}
