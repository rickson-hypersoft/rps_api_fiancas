<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Delinquencies;

use App\Http\Controllers\Controller;
use App\Http\Requests\DelinquenciesRequest;
use App\Http\Resources\DelinquenciesResource;
use App\Http\Resources\Propostal\PropostalResource;
use App\Models\Delinquencies;
use App\Models\Propostal\Propostal;
use Illuminate\Http\Request;

class DelinquenciesController extends Controller
{
    public function index(Request $request, string | int $idImobiliaria): \Symfony\Component\HttpFoundation\Response
    {
        $perPage = $request->get('per_page', 10); // quantidade por página, default 15

        $delinquencies = Delinquencies::where('ID_IMOBILIARIA', $idImobiliaria)
                // Filtro por nome (via relação com contratos)
            ->when($request->filled('nome'), function ($query) use ($request): void {
                $query->whereHas('propostal', function ($q) use ($request): void {
                    $q->where('PESSOA_NOME', 'like', '%' . $request->nome . '%');
                });
            })
                // Filtro por CPF (via relação com contratos)
            ->when($request->filled('cpf'), function ($query) use ($request): void {
                $cpf = preg_replace('/\D/', '', $request->cpf); // remove pontos e traços
                $query->whereHas('propostal', function ($q) use ($cpf): void {
                    $q->where('PESSOA_DOC', $cpf);
                });
            })
                // Filtro por status
            ->when($request->filled('status'), function ($query) use ($request): void {
                $searchIso = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string) $request->status);
                $query->whereRaw('UPPER(STATUS) LIKE UPPER(?)', ["%$searchIso%"]);
                // $query->whereAnd('STATUS', utf8_encode($request->status));
            })
                // Filtro por data
            ->when($request->filled('data_inicial') || $request->filled('data_final'), function ($query) use ($request): void {
                if ($request->filled('data_inicial') && $request->filled('data_final')) {
                    $query->whereBetween('VENCIMENTO_ORIGINAL', [
                        $request->data_inicial,
                        $request->data_final,
                    ]);
                } elseif ($request->filled('data_inicial')) {
                    $query->whereDate('VENCIMENTO_ORIGINAL', $request->data_inicial);
                } elseif ($request->filled('data_final')) {
                    $query->whereDate('VENCIMENTO_ORIGINAL', $request->data_final);
                }
            })
                // Filtro por valor
            ->when($request->filled('valor_inicial') || $request->filled('valor_final'), function ($query) use ($request): void {
                if ($request->filled('valor_inicial') && $request->filled('valor_final')) {
                    $query->whereBetween('VALOR_ORIGINAL', [
                        $request->valor_inicial,
                        $request->valor_final,
                    ]);
                } elseif ($request->filled('valor_inicial')) {
                    $query->where('VALOR_ORIGINAL', $request->valor_inicial);
                } elseif ($request->filled('valor_final')) {
                    $query->where('VALOR_ORIGINAL', $request->valor_final);
                }
            })
            ->paginate($perPage);

        return DelinquenciesResource::collection($delinquencies)
            ->response()
            ->setStatusCode(200);
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
        // Buscar a primeira delinquência para obter o CONTRATO_ID
        $delinquency = Delinquencies::where('ID_IMOBILIARIA', $idImobiliaria)
            ->findOrFail($id);

        // Buscar todas as delinquências desse mesmo contrato
        $delinquencies = Delinquencies::where('ID_IMOBILIARIA', $idImobiliaria)
            ->where('CONTRATO_ID', $delinquency->CONTRATO_ID)
            ->get();

        // Buscar a proposta vinculada
        $propostal = Propostal::where('ID', $delinquency->CONTRATO_ID)->first();

        return response()->json([
            'propostal'     => new PropostalResource($propostal),
            'delinquencies' => DelinquenciesResource::collection($delinquencies),
        ]);
    }

    public function destroy(int | string $id)
    {
        $delinquencies = Delinquencies::findOrFail($id);
        $delinquencies->delete();

        return response()->json(null, 204);
    }
}
