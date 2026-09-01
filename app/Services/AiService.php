<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Gerbang ke microservice EJT AI Core (FastAPI).
 *
 * Laravel hanya menerima request dari frontend (lewat Sanctum), lalu
 * meneruskan ke FastAPI dengan shared secret pada header `X-AI-Secret`.
 * Chat di-streaming (SSE); trip plan berupa JSON.
 */
class AiService
{
    public function baseUrl(): string
    {
        return rtrim((string) config('services.ai.base_url', ''), '/');
    }

    public function secretHeader(): string
    {
        return (string) config('services.ai.secret_header', 'X-AI-Secret');
    }

    public function apiKey(): string
    {
        return (string) config('services.ai.api_key', '');
    }

    /**
     * Header yang wajib ada di tiap request ke FastAPI.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return [
            $this->secretHeader() => $this->apiKey(),
            'Accept' => 'application/json',
        ];
    }

    /**
     * Kirim pesan chat ke FastAPI dan stream balik respons SSE (event: text).
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(string):void  $write   callback yang menerima potongan mentah
     */
    public function streamChat(array $payload, callable $write): void
    {
        $url = $this->baseUrl().config('services.ai.chat_endpoint');

        // Bangun header indexed (bukan assoc) agar sesuai format CURLOPT_HTTPHEADER
        $curlHeaders = [];
        foreach ($this->headers() as $key => $value) {
            $curlHeaders[] = "$key: $value";
        }
        $curlHeaders[] = 'Accept: text/event-stream';
        $curlHeaders[] = 'Content-Type: application/json';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use ($write): int {
                $write($chunk);
                return strlen($chunk);
            },
        ]);

        curl_exec($ch);

        if (curl_errno($ch) !== CURLE_OK) {
            $write($this->errorEvent('Terjadi kesalahan saat menghubungi layanan AI.'));
        }

        curl_close($ch);
    }

    /**
     * Minta Smart Trip Planner.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function tripPlan(array $payload): array
    {
        $url = $this->baseUrl().config('services.ai.trip_plan_endpoint');

        $request = Http::withHeaders($this->headers())
            ->timeout((int) (config('services.ai.timeout') ?: 60))
            ->acceptJson();

        $response = $request->post($url, $payload);

        if ($response->failed()) {
            return [
                'status' => 'error',
                'message' => 'Layanan AI tidak tersedia.',
                'data' => ['status_code' => $response->status()],
            ];
        }

        return $response->json();
    }

    /**
     * Format event SSE `error` agar frontend bisa menangkap kegagalan.
     */
    private function errorEvent(string $message): string
    {
        $json = json_encode(['message' => $message], JSON_UNESCAPED_UNICODE);

        return "event: error\ndata: {$json}\n\n";
    }
}
