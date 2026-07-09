<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardAdminController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        // Akan diisi di Step 18
        return response()->json(['message' => 'Endpoint ini akan diisi di Step 18'], 501);
    }
}