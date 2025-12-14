<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Symbol;
use Illuminate\Http\JsonResponse;

class SymbolController extends Controller
{
    /**
     * Get all active trading symbols.
     */
    public function index(): JsonResponse
    {
        $symbols = Symbol::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('code')
            ->get();

        return response()->json($symbols);
    }
}

