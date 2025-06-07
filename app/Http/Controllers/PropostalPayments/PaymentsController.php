<?php

declare(strict_types=1);

namespace App\Http\Controllers\PropostalPayments;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\Propostal\PropostalPayments;

class PaymentsController extends Controller
{
    public function index(string|int $idMovi) {
        $payments = PropostalPayments::where("ID_MOVI", "=", $idMovi)->get();
        return response()->json($payments);
    }

    public function store(Request $request) {
        dd($request->all());
        $validator = Validator::make($request->all(), [
            'ID' => 'required|numeric',
            'ID_IMOBILIARIA' => 'required|numeric',
            'ID_MOVI'        => 'required|numeric',
            'ID_INTEGRACAO'           => 'required|numeric',
            'ID_USUARIO'           => 'nullable|numeric',
            'METODO_PAGAMENTO'       => 'required|string|max:50',
            'VALOR'           => 'required|numeric',
            'STATUS'            => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        /** @var array<string, string|null> $validated */
        $validated = $validator->validated();
        $validated['data'] = date('Y-m-d');
        $validated['hora'] = date('H:i:s');

        $paymentData = $this->convertIsoAndTransformUpperCase($validated);

        /** @var array<string, mixed> $attributes */
        $payment = $paymentData;

        $payment = PropostalPayments::create($payment);

        return response()->json([
            "success" => true,
            "message" => "Pagamento criado com sucesso!",
        ], 201);
    }
}
