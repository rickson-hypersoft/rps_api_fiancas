<?php

declare(strict_types=1);

namespace App\Http\Controllers\PropostalPayments;

use Illuminate\Http\Request;
use App\Models\Propostal\Propostal;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\Asaas\AsaasClientService;
use App\Models\Propostal\PropostalPayments;
use App\Actions\Asaas\CreateOrUpdateAsaasCustomerAction;

class PaymentAsaasController extends Controller
{
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
