<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Http\Resources\Propostal\PropostalIndexResource;
use App\Models\Propostal\Propostal;

use Illuminate\Http\Request;

class AssetsController extends Controller
{
    public function find(string $linkHash)
    {
        $query = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();

        $propostal = new PropostalIndexResource($query);

        return response()->json(['data' => $propostal]);
    }

    public function faceId(Request $request, string $linkHash)
    {
        $asset = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();
        $data  = ['PROPOSTA_CREDITO_STATUS' => 'Aguardando Pagamento', 'FACIAL' => 1];

        $asset->update($data);

        return response()->json("Facial atualizda");
    }
}
