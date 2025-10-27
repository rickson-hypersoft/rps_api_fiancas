<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Http\Resources\DelinquenciesResource;
use App\Http\Resources\Propostal\PropostalIndexResource;
use App\Models\Propostal\Propostal;
use App\Models\Propostal\PropostalPayments;
use App\Services\Asaas\AsaasClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssetsController extends Controller
{
    public function __construct(protected AsaasClientService $service)
    {
    }

    public function index(Request $request, string | int $idImobiliaria)
    {
        if ($idImobiliaria === 0 || ($idImobiliaria === '' || $idImobiliaria === '0')) {
            return response()->json(['success' => false, 'message' => 'ID da imobiliária é obrigatório.'], 400);
        }

        $query = Propostal::query()
            ->where('ID_IMOBILIARIA', $idImobiliaria)
            ->whereNotNull('CONTRATO_STATUS');

        // Filtro por status
        if ($request->filled('status')) {
            $query->where('CONTRATO_STATUS', $request->get('status'));
        } else {
            $query->where('CONTRATO_STATUS', 'Ativo');
        }

        // Filtro por nome (case-insensitive)
        if ($request->filled('search')) {
            $search    = $request->input('search');
            $isNumeric = is_numeric($search);
            $length    = strlen((string) $search);

            $query->where(function ($q) use (
                $search,
                $isNumeric,
                $length
            ): void {
                if ($isNumeric && $length >= 11 && $length <= 14) {
                    $q->orWhere('PESSOA_DOC', 'like', "%$search%"); // busca só números no DB também precisa estar nesse formato
                } else {
                    if ($isNumeric) {
                        $q->orWhere('ID', 'like', "%$search%");
                    }

                    // Filtrar por nome
                    $searchIso = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string) $search);
                    $q->orWhereRaw('UPPER(PESSOA_NOME) LIKE UPPER(?)', ["%$searchIso%"]);

                    // Filtrar por tag do imóvel
                    $q->orWhere('IMOVEL_TAG', 'like', "%$search%");
                }
            });
        }

        if ($request->filled('created_at')) {
            $query->where('DATA', '=', $request->input('created_at'));
        }

        if ($request->filled('pendences') && $request->input('pendences') == 'Pendentes') {
            $query->where(function ($q): void {
                $q->where('ANX_CONTRATO', 0)
                    ->orWhereNull('ANX_CONTRATO')
                    ->orWhere('ANX_VISTORIA', 0)
                    ->orWhereNull('ANX_VISTORIA');
            });
        }

        // Ordenação e paginação
        $assets = $query->orderBy('ID', 'desc')->paginate(10);

        $contratos = Propostal::selectRaw("
    CASE
        WHEN CONTRATO_STATUS = 'Ativo' THEN 'Ativo'
        WHEN CONTRATO_STATUS LIKE 'Pendente%' THEN 'Pendente'
        WHEN CONTRATO_STATUS = 'Reprovada' THEN 'Em renovação'
        WHEN CONTRATO_STATUS = 'Cancelado' THEN 'Cancelado'
    END AS STATUS_PERSONALIZADO,
    COUNT(*) AS total
")
            ->where('ID_IMOBILIARIA', $idImobiliaria)
            ->groupBy('STATUS_PERSONALIZADO')
            ->get();

        return response()->json([
            'success'   => true,
            'data'      => PropostalIndexResource::collection($assets),
            'contratos' => $contratos,
            'meta'      => [
                'current_page' => $assets->currentPage(),
                'from'         => $assets->firstItem(),
                'last_page'    => $assets->lastPage(),
                'links'        => $assets->linkCollection(), // ✅ Links padrão do Laravel
                'path'         => $request->url(),
                'per_page'     => $assets->perPage(),
                'to'           => $assets->lastItem(),
                'total'        => $assets->total(),
            ],
        ]);
    }

    public function find(string $linkHash)
    {
        $query = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();

        $propostal = new PropostalIndexResource($query);

        return response()->json(['data' => $propostal]);
    }

    public function findAsset(string $idImobiliaria, string $idContrato)
    {
        $query = Propostal::where('ID', '=', $idContrato)->firstOrFail();

        $propostal = new PropostalIndexResource($query);

        $canDelinquencies = DB::table('INADIMPLENCIAS')
            ->where('ID_IMOBILIARIA', $idImobiliaria)
            ->where('CONTRATO_ID', $idContrato)
            ->get();

        $canDelinquencies = $canDelinquencies->isNotEmpty() ? $canDelinquencies->map(fn ($item): DelinquenciesResource => new DelinquenciesResource($item)) : [];

        return response()->json(['data' => $propostal, 'inadimplencia' => $canDelinquencies]);
    }

    public function faceId(Request $request, string $linkHash)
    {
        $asset = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();
        $data  = ['PROPOSTA_CREDITO_STATUS' => 'Aguardando Pagamento', 'FACIAL' => 1];

        $asset->update($data);

        return response()->json("Facial atualizda");
    }

    public function markTermActive(string $linkHash)
    {
        $asset = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();
        $data  = ['DATA_ATIVACAO_TERMO' => now()->format('Y-m-d'), 'HORA_ATIVACAO_TERMO' => now()->format('H:i:s'), 'TERMO_ATIVO' => 1];

        $asset->update($data);

        return response()->json("Termo de aceite atualizado");
    }

    public function storeTerm(Request $request)
    {
        // validação simples
        $request->validate([
            'file'      => 'required|file|mimes:pdf',
            'link_hash' => 'required|string',
        ]);

        $file     = $request->file('file');
        $linkHash = $request->input('link_hash');

        // cria pasta se não existir
        $path = "termos/{$linkHash}.pdf";

        Storage::disk('public')->put("termos/{$linkHash}.pdf", file_get_contents($file->getRealPath()));

        return response()->json([
            'message' => 'PDF salvo com sucesso!',
            'path'    => $path,
        ]);
    }

    public function download(string $link)
    {
        $filePath = 'termos/' . $link . '.pdf'; // caminho correto dentro do disco 'public'

        // Checa se existe
        if (! Storage::disk('public')->exists($filePath)) {
            return response()->json(['message' => 'Arquivo não encontrado.'], 404);
        }

        // Retorna o arquivo para download
        return Storage::disk('public')->download($filePath);
    }

    public function cancelarContrato(Request $request, $idContrato)
    {
        $asset = Propostal::where('ID', '=', $idContrato)->firstOrFail();

        $asset->update([
            'CONTRATO_STATUS'      => 'Cancelado',
            'MOTIVO_CANCELAMENTO'  => utf8_decode($request->input('motivo_rescisao')),
            'DETALHE_CANCELAMENTO' => utf8_decode($request->input('detalhe')),
            'DATA_CANCELAMENTO'    => now(),
            'DATA_ENTREGA_CHAVE'   => $request->input('data_entrega'),
        ]);

        $this->processarEstornoContrato($asset);

        return response()->json([
            'message'  => 'Contrato cancelado com sucesso',
            'contrato' => new PropostalIndexResource($asset),
        ]);
    }

    public function processarEstornoContrato($contrato): bool
    {
        // 1. Buscar todos os pagamentos relacionados ao contrato
        $propostalPayments = PropostalPayments::where('ID_MOVI', $contrato->ID)->get();

        foreach ($propostalPayments as $payment) {
            // 2. Buscar detalhes do pagamento na API do Asaas
            $paymentDetail = $this->service->getPaymentById($payment->ID_PAGAMENTO_INTEGRACAO);

            // 3. Só continua se for parcelamento e do tipo cartão
            if (empty($paymentDetail['installment'])) {
                continue;
            }

            if (($paymentDetail['billingType'] ?? null) !== 'CREDIT_CARD') {
                continue;
            }

            $installmentId = $paymentDetail['installment'];

            // 4. Buscar todas as parcelas vinculadas ao parcelamento
            $installmentResp = $this->service->getInstallmentId($installmentId);
            $parcelas        = $installmentResp['data'] ?? [];

            foreach ($parcelas as $p) {
                // Buscar detalhe da parcela (para garantir que status existe)
                $parcelaDetail = $this->service->getPaymentById($p['id']);

                // 5. Cancelar apenas as parcelas futuras (pendentes)
                if (in_array($parcelaDetail['status'], ['PENDING', 'AWAITING_PAYMENT'])) {
                    $this->service->cancelPayment($p['id']); // DELETE /payments/{id}
                }
            }
        }

        return true;
    }

    public function getPagamentos($idContrato)
    {
        $propostalsPayments = PropostalPayments::where('ID_MOVI', '=', $idContrato)->first();

        $exist = $this->service->getPaymentById($propostalsPayments->ID_PAGAMENTO_INTEGRACAO);

        if ($exist !== []) {
            return response()->json([
                'success'    => true,
                'pagamentos' => $propostalsPayments,
            ]);
        }

        return response()->json([
            'success'    => false,
            'pagamentos' => 'Não foi encontrado pagamento com esse ID',
        ]);
    }
}