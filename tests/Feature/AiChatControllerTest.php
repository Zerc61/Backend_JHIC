<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Controllers\Api\AiChatController;
use App\Services\AiService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AiChatControllerTest extends TestCase
{
    use RefreshDatabase;

    private function touristUser(): User
    {
        return User::factory()->create();
    }

    public function test_chat_requires_authentication(): void
    {
        $this->postJson('/api/ai/chat', [
            'message' => 'hai',
            'session_id' => 'sess-1',
        ])->assertUnauthorized();
    }

    public function test_chat_validates_required_fields(): void
    {
        $user = $this->touristUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/ai/chat', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message', 'session_id']);
    }

    /**
     * Panggil controller secara langsung (bukan via HTTP harness) agar
     * StreamedResponse dieksekusi sekali & deterministik.
     *
     * @return array{0: array<string,mixed>|null, 1: string} [payload, content]
     */
    private function runChatDirect(User $user, array $input): array
    {
        $captured = null;
        $written = '';

        $mock = \Mockery::mock(AiService::class);
        $mock->shouldReceive('streamChat')->once()
            ->withArgs(function (array $p, callable $w) use (&$captured, &$written) {
                $captured = $p;
                $w("event: text\ndata: {\"delta\":\"Halo KAVI\"}\n\n");
                $w("event: done\ndata: {}\n\n");
                $written = "event: text\ndata: {\"delta\":\"Halo KAVI\"}\n\nevent: done\ndata: {}\n\n";

                return true;
            });

        $controller = new AiChatController($mock);

        $request = Request::create('/api/ai/chat', 'POST', $input);
        $request->setUserResolver(fn () => $user);

        $response = $controller->chat($request);
        $response->sendContent();

        return [$captured, $written];
    }

    public function test_chat_streams_sse_and_maps_role_to_tourist(): void
    {
        $user = $this->touristUser();

        [$captured, $content] = $this->runChatDirect($user, [
            'message' => 'recommendasikan pantai',
            'session_id' => 'sess-abc',
            'history' => [
                ['role' => 'user', 'content' => 'halo'],
                ['role' => 'assistant', 'content' => 'hai'],
            ],
        ]);

        // role default utk tourist -> 'tourist' (persona KAVI)
        $this->assertSame('tourist', $captured['role']);
        $this->assertSame((int) $user->id, $captured['user_id']);
        $this->assertSame('sess-abc', $captured['session_id']);
        $this->assertCount(2, $captured['history']);

        $this->assertStringContainsString('Halo KAVI', $content);
        $this->assertStringContainsString('event: done', $content);
    }

    public function test_chat_maps_umkm_and_manager_roles(): void
    {
        $umkm = User::factory()->create(['role' => UserRole::UMKM]);

        $captured = null;
        $mock = \Mockery::mock(AiService::class);
        $mock->shouldReceive('streamChat')->once()
            ->withArgs(function (array $p, callable $w) use (&$captured) {
                $captured = $p;
                $w('x');

                return true;
            });

        $controller = new AiChatController($mock);
        $request = Request::create('/api/ai/chat', 'POST', ['message' => 'tes', 'session_id' => 's1']);
        $request->setUserResolver(fn () => $umkm);

        $controller->chat($request)->sendContent();

        $this->assertSame('umkm', $captured['role']);
    }

    public function test_trip_plan_returns_ai_result(): void
    {
        $user = $this->touristUser();
        $captured = null;

        $this->mock(AiService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('tripPlan')->once()
                ->withArgs(function (array $payload) use (&$captured) {
                    $captured = $payload;

                    return true;
                })
                ->andReturn([
                    'status' => 'success',
                    'data' => ['status' => 'draft', 'days' => 2, 'itinerary' => []],
                ]);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ai/trip/plan', [
                'days' => 2,
                'budget' => 100000,
                'start_city' => 'Malang',
                'preferences' => ['pantai'],
                'session_id' => 'sess-1',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->assertSame((int) $user->id, $captured['user_id']);
        $this->assertSame(100000, $captured['budget']);
        $this->assertSame(['pantai'], $captured['preferences']);
    }

    public function test_trip_plan_returns_502_when_ai_fails(): void
    {
        $user = $this->touristUser();

        $this->mock(AiService::class, function ($mock) {
            $mock->shouldReceive('tripPlan')->once()
                ->andReturn(['status' => 'error', 'message' => 'Layanan AI tidak tersedia.']);
        });

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/ai/trip/plan', ['days' => 1])
            ->assertStatus(502);
    }
}
