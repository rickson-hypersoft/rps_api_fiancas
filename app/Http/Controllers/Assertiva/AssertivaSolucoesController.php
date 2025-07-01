<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Assertiva;

use App\Http\Controllers\Controller;
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
            $score = $this->assertivaService->checkScore($request->document);

            return response()->json(['data' => $score]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
