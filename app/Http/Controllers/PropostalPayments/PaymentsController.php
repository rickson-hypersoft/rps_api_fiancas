<?php

declare(strict_types = 1);

namespace App\Http\Controllers\PropostalPayments;

use App\Http\Controllers\Controller;
use App\Models\Propostal\PropostalPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentsController extends Controller
{
    public function index(string | int $idMovi)
    {
        $payments = PropostalPayments::where("ID_MOVI", "=", $idMovi)->get();

        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ID'                      => 'required|numeric',
            'ID_IMOBILIARIA'          => 'required|numeric',
            'ID_MOVI'                 => 'required|numeric',
            'ID_USUARIO_INTEGRACAO'   => 'required|numeric',
            'ID_USUARIO'              => 'nullable|numeric',
            'METODO_PAGAMENTO'        => 'required|string|max:50',
            'VALOR'                   => 'required|numeric',
            'STATUS'                  => 'required|string|max:100',
            'ID_PAGAMENTO_INTEGRACAO' => 'required|string|max:100',
            'DATA_VENCIMENTO'         => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        /** @var array<string, string|null> $validated */
        $validated         = $validator->validated();
        $validated['data'] = date('Y-m-d');
        $validated['hora'] = date('H:i:s');

        $paymentData = $this->convertIsoAndTransformUpperCase($validated);

        $payment = $paymentData;

        PropostalPayments::create($payment);

        return response()->json([
            "success" => true,
            "message" => "Pagamento criado com sucesso!",
        ], 201);
    }
}
