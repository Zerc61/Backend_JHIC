<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardUmkmController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        // Akan diisi di Step 17
        return response()->json(['message' => 'Endpoint ini akan diisi di Step 17'], 501);
    }

    public function products(Request $request): JsonResponse
    {
        // Akan diisi di Step 17
        return response()->json(['message' => 'Endpoint ini akan diisi di Step 17'], 501);
    }

    public function orders(Request $request): JsonResponse
    {
        // Akan diisi di Step 17
        return response()->json(['message' => 'Endpoint ini akan diisi di Step 17'], 501);
    }
}