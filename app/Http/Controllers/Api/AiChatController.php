<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gerbang AI (KAVI/RAKA/MAJA) untuk frontend.
 *
 * Frontend dilarang bicara langsung ke FastAPI. Endpoint ini menerima request
 * dari user terautentikasi (Sanctum), lalu meneruskan ke EJT AI Core dengan
 * shared secret. Chat di-streaming (SSE); trip plan berupa JSON draft.
 */
class AiChatController extends Controller
{
    public function __construct(private AiService $ai)
    {
    }

    /**
     * POST /api/ai/chat — proxy chat streaming (SSE) ke FastAPI.
     */
    public function chat(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'session_id' => ['required', 'string', 'max:64'],
            'history' => ['sometimes', 'array'],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string'],
        ]);

        $user = $request->user();

        $payload = [
            'user_id' => (int) $user->id,
            'role' => $this->resolveRole($user->role),
            'message' => $validated['message'],
            'session_id' => $validated['session_id'],
            'history' => $validated['history'] ?? [],
        ];

        return response()->stream(function () use ($payload): void {
            $this->ai->streamChat($payload, function (string $chunk): void {
                echo $chunk;
                if (function_exists('ob_flush')) {
                    ob_flush();
                }
                flush();
            });
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * POST /api/ai/trip/plan — Smart Trip Planner (hasil draft).
     */
    public function tripPlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:14'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_city' => ['nullable', 'string', 'max:120'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*' => ['string', 'max:120'],
            'session_id' => ['sometimes', 'string', 'max:64'],
            // Data kandidat (opsional) dari frontend utama Laravel
            'destinations' => ['sometimes', 'array'],
            'hotels' => ['sometimes', 'array'],
        ]);

        $user = $request->user();

        $payload = [
            'user_id' => (int) $user->id,
            'days' => $validated['days'],
            'budget' => $validated['budget'] ?? 0,
            'start_city' => $validated['start_city'] ?? null,
            'preferences' => $validated['preferences'] ?? [],
            'session_id' => $validated['session_id'] ?? '',
            'destinations' => $validated['destinations'] ?? [],
            'hotels' => $validated['hotels'] ?? [],
        ];

        $result = $this->ai->tripPlan($payload);

        if (($result['status'] ?? '') === 'error') {
            return response()->json([
                'message' => $result['message'] ?? 'Layanan AI tidak tersedia.',
            ], 502);
        }

        return response()->json($result);
    }

    /**
     * Map role User (enum) -> role yang dipahami FastAPI (persona).
     */
    private function resolveRole(?UserRole $role): string
    {
        return match ($role) {
            UserRole::UMKM => 'umkm',
            UserRole::MANAGER => 'manager',
            default => 'tourist', // tourist & admin -> persona KAVI
        };
    }
}
