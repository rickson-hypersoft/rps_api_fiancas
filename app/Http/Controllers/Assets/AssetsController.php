<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assets;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Propostal\Propostal;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\Propostal\PropostalPayments;
use App\Http\Resources\Propostal\PropostalIndexResource;

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
        $asset = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();
        $faceId = ['FACIAL' => 1];

        $asset->update($faceId);

        return response()->json("Facial atualizda");
    }

    private function createClientAsaas(Request $request, string $linkHash)
    {
        $token = config('asaas.token');
        $asaasUrl = 'https://api-sandbox.asaas.com/v3/customers';

        // Busca a proposta
        $clientExist = Propostal::where('LINK_HASH', $linkHash)->firstOrFail();

        // Se não houver ID_INTEGRACAO, cria o cliente no Asaas
        if (empty($clientExist->ID_INTEGRACAO)) {
            $clientPayload = [
                'name' => $clientExist->PESSOA_NOME,
                'cpfCnpj' => $clientExist->PESSOA_DOC,
                'email' => $clientExist->PESSOA_EMAIL,
                'mobilePhone' => $clientExist->PESSOA_TELEFONE,
                'address' => $clientExist->IMOVEL_ENDERECO,
                'addressNumber' => $clientExist->IMOVEL_NUMERO,
                'complement' => $clientExist->IMOVEL_COMPLEMENTO,
                'province' => $clientExist->IMOVEL_BAIRRO,
                'postalCode' => $clientExist->IMOVEL_CEP,
                'externalReference' => $clientExist->ID,
            ];

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'access_token' => $token
            ])->post($asaasUrl, $clientPayload);

            // Se falhar ao criar o cliente
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Erro ao criar cliente no Asaas.',
                    'details' => $response
                ], $response->status());
            }

            // Salva o ID do cliente no banco
            $idIntegracao = $response->json('id');
            $clientExist->update(['ID_INTEGRACAO' => $idIntegracao]);
        }

        // Retorna apenas o ID_INTEGRACAO necessário para criar o pagamento
        return response()->json([
            'success' => true,
            'id_integracao' => $clientExist->ID_INTEGRACAO,
            'data'          => $clientExist
        ]);
    }

    public function checkout(Request $request, string $linkHash)
    {
        /** @var \Illuminate\Http\JsonResponse $response */
        $response = $this->createClientAsaas($request, $linkHash);
        $data = $response->getData(true);

        if (!isset($data['success']) || !$data['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar cliente',
                'details' => $data['error'] ?? null
            ], 500);
        }

        $idIntegracao = $data['id_integracao'];

        $paymentMethod = $request->input('payment');

        // Criar pagamento
        $token = config('asaas.token');
        $dataPayment = [
            'billingType' => $paymentMethod,
            'customer'    => $idIntegracao,
            'value'       => $data['data']['PROPOSTA_TOTAL_VALOR'],
            'dueDate'    => date('Y-m-d')
        ];

        $query = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();
        $propostal = new PropostalIndexResource($query);

        $pagamentos = [
            "ID_IMOBILIARIA" => $propostal['ID_IMOBILIARIA'],
            "ID_MOVI"  => $propostal['ID'],
            "ID_INTEGRACAO"  => $idIntegracao,
            "METODO_PAGAMENTO"  => $paymentMethod,
            "VALOR"  => $propostal['ID_IMOBILIARIA'],
            "STATUS"  => $propostal['ID_IMOBILIARIA'],
            "ID_USUARIO" => null,
            "DATA" => date('Y-m-d'),
            "HORA" => date('H:i:s'),
        ];

        // Inserir na tabela propostas pagamentos
        $payment = PropostalPayments::create($pagamentos);
        dd($payment);

        if ($responsePagamentos->successful()) {
            // Tudo certo
            $dados = $responsePagamentos->json();
        } else {
            // Algo deu errado
            dd($responsePagamentos->status(), $responsePagamentos->body());
        }

        // Se falhar ao criar o cliente
        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao criar cliente no Asaas.',
                'details' => $response
            ], $response->status());
        }

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'access_token' => $token
        ])->post('https://api-sandbox.asaas.com/v3/payments', $dataPayment);

        return response()->json([
            'success' => true,
            'response' => $response->json()
        ]);
    }
}
