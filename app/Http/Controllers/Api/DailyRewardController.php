<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EngagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DailyRewardController extends Controller
{
    public function __construct(private EngagementService $engagement)
    {
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->engagement->dailyStatus($request->user()),
        ]);
    }

    public function claim(Request $request): JsonResponse
    {
        $result = $this->engagement->claimDaily($request->user());

        if (! $result['valid']) {
            throw ValidationException::withMessages([
                'daily' => $result['message'],
            ]);
        }

        return response()->json([
            'message' => $result['voucher_code']
                ? "Hari ke-{$result['day']}! Kamu dapat +{$result['coins']} EJTCoin dan voucher {$result['voucher_code']}"
                : "Hari ke-{$result['day']} — kamu dapat +{$result['coins']} EJTCoin",
            'data' => $result,
        ]);
    }
}