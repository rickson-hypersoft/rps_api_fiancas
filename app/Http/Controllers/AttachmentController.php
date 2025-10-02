<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Resources\AttachamentResource;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttachmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestData = $request->all();

        if ($requestData['id_movi'] && $requestData['id_imobiliaria']) {
            $attachment = Attachment::where('ID_MOVI', '=', $request->all()['id_movi'])
                ->where('ID_IMOBILIARIA', '=', $request->all()['id_imobiliaria'])
                ->get();
        } else {
            $attachment = Attachment::all();
        }

        return response()->json(
            AttachamentResource::collection($attachment)->response()->getData(true)['data']
        );
    }

    public function find(string | int $id): JsonResponse
    {
        $attachment = Attachment::query()->where("ID", "=", $id)->firstOrFail();

        return response()->json(['data' => $attachment]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_imobiliaria'        => 'required|numeric',
            'id_movi'               => 'required|numeric',
            'movi'                  => 'nullable|string|max:50',
            'movi_sub'              => 'nullable|string|max:50',
            'data'                  => 'required|date',
            'nome_arquivo_original' => 'required|string|max:100',
            'nome_arquivo'          => 'nullable|string|max:100',
            'descricao'             => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        /** @var array<string, string|null> $validated */
        $validated = $validator->validated();

        $attachmentData = $this->convertIsoAndTransformUpperCase($validated);

        /** @var array<string, mixed> $attributes */
        $attributes = $attachmentData;

        Attachment::create($attributes);

        return response()->json([
            "success" => true,
            "message" => "Anexo criado com sucesso!",
        ], 201);
    }

    public function update(int | string $id, Request $request): JsonResponse
    {
        $attachment = Attachment::query()->where("ID", "=", $id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'id_imobiliaria'        => 'required|numeric',
            'id_movi'               => 'required|numeric',
            'movi'                  => 'nullable|string|max:50',
            'movi_sub'              => 'nullable|string|max:50',
            'data'                  => 'required|timestamp',
            'nome_arquivo_original' => 'required|string|max:100',
            'nome_arquivo'          => 'nullable|string|max:100',
            'descricao'             => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        /** @var array<string, string|null> $validated */
        $validated = $validator->validated();

        $attachmentData = $this->convertIsoAndTransformUpperCase($validated);

        /** @var array<string, mixed> $attributes */
        $attributes = $attachmentData;

        $attachment->update($attributes);

        return response()->json([
            "success" => true,
            "message" => "Anexo atualizado com sucesso!",
            "data"    => $attachment,
        ], 200);
    }

    public function exists(Request $request): JsonResponse
    {
        $exists = Attachment::where('ID_IMOBILIARIA', $request->id_imobiliaria)
            ->where('ID_MOVI', $request->id_movi)
            ->where('NOME_ARQUIVO_ORIGINAL', $request->nome_arquivo_original)
            ->exists(); // aqui deve ser exists(), não get()

        return response()->json(['exists' => $exists]);
    }

    public function search(Request $request): JsonResponse
    {
        $attachments = Attachment::where('ID_IMOBILIARIA', $request->id_imobiliaria)
            ->where('ID_MOVI', $request->id_movi)
            ->whereRaw('LOWER(MOVI) = ?', [strtolower($request->movi ?? 'contratos')])
            ->whereRaw('MOVI_SUB = ?', mb_convert_encoding($request->movi_sub, 'ISO-8859-1'))
            ->get(['ID', 'ID_IMOBILIARIA', 'ID_MOVI', 'NOME_ARQUIVO', 'NOME_ARQUIVO_ORIGINAL', 'MOVI_SUB', 'DATA', 'DESCRICAO', 'MOVI']);

        $anexos = AttachamentResource::collection($attachments);

        return response()->json($anexos);
    }
}
