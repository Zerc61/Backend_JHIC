<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EngagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuestController extends Controller
{
    public function __construct(private EngagementService $engagement)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->engagement->quests($request->user()),
        ]);
    }

    public function claim(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string',
        ]);

        $result = $this->engagement->claimQuest($request->user(), (string) $request->slug);

        if (! $result['valid']) {
            throw ValidationException::withMessages([
                'slug' => $result['message'],
            ]);
        }

        return response()->json([
            'message' => "Hadiah quest didapatkan: +{$result['coins']} EJTCoin",
            'data' => $result,
        ]);
    }
}