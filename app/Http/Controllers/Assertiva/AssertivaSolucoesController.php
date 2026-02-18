<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Assertiva;

use App\Http\Controllers\Controller;
use App\Http\Resources\Propostal\PropostalIndexResource;
use App\Models\Propostal\Propostal;
use App\Services\Assertiva\AssertivaSolucoesService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            \Log::info('Retorno', [
                'dados' => $dataReturn,
            ]);

            $propostal->update([
                'PROTOCOLO_FACIAL'       => $dataReturn['data']['protocolo'],
                'PEDIDO_ID_FACIAL'       => $dataReturn['data']['pedidoId'],
                'PARTE_ID'               => $dataReturn['data']['partes'][0]['parteId'],
                'PROTOCOLO_PARTE_FACIAL' => $dataReturn['data']['partes'][0]['protocolo'],
            ]);

            \Log::info('Propostal', [
                'dados' => $propostal,
            ]);

            $maxAttempts = 10;
            $attempt     = 0;
            // $linkFacial  = null;
            $linkFacial = $this->assertivaService->getLink($propostal);

            // while ($attempt < $maxAttempts) {
            //     $attempt++;

            //     if (isset($linkFacial['status']) && $linkFacial['status'] === true && isset($linkFacial['data']['url'])) {
            //         break; // link pronto
            //     }

            //     sleep(2);
            // }

            if (! isset($linkFacial['data']['url'])) {
                \Log::warning("Link ainda não disponível para proposta {$propostal->ID}");

                return response()->json([
                    'error' => 'Link facial ainda não disponível, tente novamente mais tarde.',
                ], 202);
            }

            $propostal->update([
                'LINK_FACIAL' => $linkFacial['data']['url'],
            ]);

            \Log::info('Proposta 2', [
                'dados' => $propostal,
            ]);

            return response()->json([
                'data' => new PropostalIndexResource($propostal),
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $body = $e->hasResponse()
                ? $e->getResponse()->getBody()->getContents()
                : '';

            if (! mb_check_encoding($body, 'UTF-8')) {
                $body = '[binary or invalid utf-8 response]';
            }

            return response()->json([
                'error' => $e->getMessage(),
                'body'  => $body,
            ], 400);
        } catch (Exception $e) {
            \Log::error('Erro Assertiva', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Erro interno na comunicação com a Assertiva.',
            ], 400);
        }
    }

    public function aprovarFacial($propostalId)
    {
        $propostal = Propostal::where('ID', '=', $propostalId)->first();
        $parteId   = $propostal->PARTE_ID;

        $dataReturn = $this->assertivaService->aprovarFacial($parteId);

        Log::info('Aprovar Facial', [
            'dados' => $dataReturn,
        ]);

        if ($dataReturn['success']) {
            $propostal->update([
                'FACIAL_SCORE_TOTAL' => null,
                'FACIAL_MATCHES'     => null,
            ]);

            return response()->json([
                'success'  => true,
                'mensagem' => 'Parte aprovada manualmente com sucesso!',
            ]);
        }

        return response()->json(['error' => $dataReturn], 400);
    }

    public function reenviarLinkFacial($propostalId)
    {
        $propostal = Propostal::where('ID', '=', $propostalId)->first();
        $parteId   = $propostal->PARTE_ID;

        $dataReturn = $this->assertivaService->reenviarLinkFacial($parteId);

        if ($dataReturn['success']) {
            $linkFacial = $this->assertivaService->getLink($propostal);

            if (! isset($linkFacial['data']['url'])) {
                return response()->json([
                    'error' => 'Link facial ainda não disponível, tente novamente mais tarde.',
                ], 202);
            }

            $propostal->update([
                'FACIAL_SCORE_TOTAL' => null,
                'FACIAL_MATCHES'     => null,
            ]);

            return response()->json([
                'success'  => true,
                'mensagem' => 'Link para validação reenviada com sucesso!',
            ]);
        }

        Log::info('Reenviar Link Facial', [
            'dados' => $dataReturn,
        ]);

        return response()->json(['error' => $dataReturn['data']], 400);
    }
}
