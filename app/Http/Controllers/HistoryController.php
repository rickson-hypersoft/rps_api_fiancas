<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Resources\HistoryResource;
use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HistoryController extends Controller
{
    public function index(string | int $idMovi)
    {
        $history = History::where('ID_MOVI', "=", $idMovi)
        ->orderBy('ID', 'ASC')
            ->get();
        $history = HistoryResource::collection($history);

        return response()->json(['data' => $history]);
    }

    public function store(Request $request)
    {
        $dataRequest = $request->all();

        $validator = Validator::make($dataRequest, [
            'id_imobiliaria' => 'required|numeric',
            'id_movi'        => 'required|numeric',
            'movi'           => 'required|string|max:50',
            'data'           => 'required|date',
            'hora'           => 'required',
            'historico'      => 'required|string|max:2000',
            'id_usuario'     => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $historyData = $validator->validated();
        $historyData = $this->convertIsoAndTransformUpperCase($historyData);

        $existing = History::where('ID_IMOBILIARIA', $historyData['ID_IMOBILIARIA'])
            ->where('ID_MOVI', $historyData['ID_MOVI'])
            ->where('MOVI', $historyData['MOVI'])
            ->where('HISTORICO', $historyData['HISTORICO'])
            ->where('ID_USUARIO', $historyData['ID_USUARIO'])
            ->first();

        if ($existing) {
            // Atualiza o existente (exemplo: atualiza o timestamp)
            $existing->update($historyData);

            return response()->json([
                "success" => true,
                "message" => "Histórico atualizado com sucesso",
            ], 200);
        }

        History::create($historyData);

        return response()->json([
            "success" => true,
            "message" => "Histórico criado com sucesso",
        ], 201);
    }

    public function update()
    {
    }
}