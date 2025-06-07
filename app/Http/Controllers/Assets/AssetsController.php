<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Assets;

use App\Actions\Asaas\CreateOrUpdateAsaasCustomerAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Propostal\PropostalIndexResource;
use App\Models\Propostal\Propostal;
use App\Models\Propostal\PropostalPayments;
use App\Services\Asaas\AsaasClientService;
use Illuminate\Http\Request;

class AssetsController extends Controller
{
    public function active(string $linkHash)
    {
        $query = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();

        $propostal = new PropostalIndexResource($query);

        return response()->json(['data' => $propostal]);
    }

    public function faceId(Request $request, string $linkHash)
    {
        $asset  = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();
        $faceId = ['FACIAL' => 1];

        $asset->update($faceId);

        return response()->json("Facial atualizda");
    }

    public function checkout(Request $request, string $linkHash)
    {
        $propostal = Propostal::where('LINK_HASH', $linkHash)->firstOrFail();

        $customerId = (new CreateOrUpdateAsaasCustomerAction(
            new AsaasClientService()
        ))->execute($propostal);

        if (! $customerId) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar/atualizar cliente no Asaas.',
            ], 500);
        }

        $asaasService = new AsaasClientService();
        $response     = $asaasService->createPayment([
            'billingType' => $request->input('payment'),
            'customer'    => $customerId,
            'value'       => $propostal->PROPOSTA_TOTAL_VALOR,
            'dueDate'     => now()->toDateString(),
        ]);

        $payment = PropostalPayments::create([
            'ID_IMOBILIARIA'        => $propostal->ID_IMOBILIARIA,
            'ID_MOVI'               => $propostal->ID,
            'ID_USUARIO_INTEGRACAO' => $customerId,
            'METODO_PAGAMENTO'      => $request->input('payment'),
            'VALOR'                 => $propostal->ID_IMOBILIARIA, // Confirme esse campo
            'STATUS'                => $propostal->ID_IMOBILIARIA, // Confirme esse campo
            'ID_USUARIO'            => 10,
            'DATA'                  => now()->toDateString(),
            'HORA'                  => now()->toTimeString(),
        ]);

        dd($payment);

        return response()->json([
            'success'  => $response['success'],
            'response' => $response['data'] ?? null,
        ]);
    }
}
