<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Assertiva;

use App\Http\Controllers\Controller;
use App\Models\Propostal\Propostal;
use App\Services\Assertiva\AssertivaSolucoesService;
use Exception;
use Illuminate\Http\Request;

class AssertivaSolucoesController extends Controller
{
    public function __construct(protected AssertivaSolucoesService $assertivaService)
    {
    }

    public function check(Request $request)
    {
        $request->validate([
            'document' => 'required|string',
        ]);

        try {
            return $this->assertivaService->checkScore($request->document);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function createSignatureAndGetLinkFacial(int | string $propostalId)
    {
        $propostal = Propostal::where('ID', '=', $propostalId)->first();

        try {
            $dataReturn = $this->assertivaService->createOrderSignature($propostal);
            $propostal->update([
                'PROTOCOLO_FACIAL' => $dataReturn['data']['protocolo'],
                'PEDIDO_ID_FACIAL' => $dataReturn['data']['pedidoId'],
            ]);

            $linkFacial = $this->assertivaService->getLink($propostal);
            $propostal->update([
                'LINK_FACIAL' => $linkFacial['data']['url'],
            ]);

            return response()->json(['
            data' => 'Criação da assinatura e recuperação do link para facial realizado com sucesso!']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
