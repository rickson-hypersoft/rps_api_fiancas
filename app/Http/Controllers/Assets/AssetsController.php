<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assets;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Propostal\Propostal;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Propostal\PropostalIndexResource;

class AssetsController extends Controller
{
    public function active(string $linkHash)
    {
        $query = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();

        $propostal = new PropostalIndexResource($query);

        return response()->json(['data' => $propostal]);
    }
}
