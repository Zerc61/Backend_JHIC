<?php

namespace Tests\Feature;

use App\Enums\TopUpStatus;
use App\Models\TopUpTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup Midtrans config
        config([
            'midtrans.server_key' => 'test_key_12345',
            'midtrans.is_production' => false,
        ]);
    }

    public function test_webhook_with_invalid_signature_is_rejected()
    {
        $user = User::factory()->create();
        $topUp = TopUpTransaction::create([
            'user_id' => $user->id,
            'amount_rupiah' => 50000,
            'rate_per_coin' => 2000,
            'coins_received' => 25,
            'status' => TopUpStatus::PENDING,
            'midtrans_order_id' => 'ORDER-123',
        ]);

        $payload = [
            'order_id' => 'ORDER-123',
            'status_code' => '200',
            'gross_amount' => '50000',
            'transaction_status' => 'settlement',
            'signature_key' => 'INVALID_SIGNATURE_12345',
        ];

        $response = $this->postJson('/api/midtrans/notification', $payload);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid signature']);

        $this->assertDatabaseHas('top_up_transactions', [
            'midtrans_order_id' => 'ORDER-123',
            'status' => TopUpStatus::PENDING->value,
        ]);
    }

    public function test_webhook_with_valid_signature_succeeds()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $topUp = TopUpTransaction::create([
            'user_id' => $user->id,
            'amount_rupiah' => 50000,
            'rate_per_coin' => 2000,
            'coins_received' => 25,
            'status' => TopUpStatus::PENDING,
            'midtrans_order_id' => 'ORDER-123',
        ]);

        // Generate valid signature
        $orderId = 'ORDER-123';
        $statusCode = '200';
        $grossAmount = '50000';
        $serverKey = config('midtrans.server_key');
        $validSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'transaction_id' => 'TX-123',
            'payment_type' => 'bank_transfer',
            'signature_key' => $validSignature,
        ];

        $response = $this->postJson('/api/midtrans/notification', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('top_up_transactions', [
            'midtrans_order_id' => 'ORDER-123',
            'status' => TopUpStatus::SUCCESS->value,
        ]);

        $wallet->refresh();
        $this->assertEquals(25, $wallet->balance);
    }

    public function test_webhook_notification_is_idempotent()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $topUp = TopUpTransaction::create([
            'user_id' => $user->id,
            'amount_rupiah' => 50000,
            'rate_per_coin' => 2000,
            'coins_received' => 25,
            'status' => TopUpStatus::PENDING,
            'midtrans_order_id' => 'ORDER-456',
        ]);

        $orderId = 'ORDER-456';
        $statusCode = '200';
        $grossAmount = '50000';
        $serverKey = config('midtrans.server_key');
        $validSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'transaction_id' => 'TX-456',
            'payment_type' => 'bank_transfer',
            'signature_key' => $validSignature,
        ];

        // Send webhook twice
        $this->postJson('/api/midtrans/notification', $payload);
        $response2 = $this->postJson('/api/midtrans/notification', $payload);

        $response2->assertStatus(200);

        // Wallet should only have 25 coins, not 50
        $wallet->refresh();
        $this->assertEquals(25, $wallet->balance);
    }

    public function test_webhook_rejects_missing_transaction()
    {
        $orderId = 'ORDER-999-NOT-EXISTS';
        $statusCode = '200';
        $grossAmount = '50000';
        $serverKey = config('midtrans.server_key');
        $validSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'signature_key' => $validSignature,
        ];

        $response = $this->postJson('/api/midtrans/notification', $payload);

        $response->assertStatus(404);
    }

    public function test_simulation_endpoint_only_available_in_local_environment()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        config(['app.env' => 'production']);

        $response = $this->postJson('/api/admin/wallet/simulate-webhook/ORDER-123');

        $response->assertStatus(404);
    }

    public function test_simulation_endpoint_available_in_local_environment()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $topUp = TopUpTransaction::create([
            'user_id' => $user->id,
            'amount_rupiah' => 50000,
            'rate_per_coin' => 2000,
            'coins_received' => 25,
            'status' => TopUpStatus::PENDING,
            'midtrans_order_id' => 'ORDER-SIM-123',
        ]);

        config(['app.env' => 'local']);

        $response = $this->postJson('/api/admin/wallet/simulate-webhook/ORDER-SIM-123');

        $response->assertStatus(200);
        $wallet->refresh();
        $this->assertEquals(25, $wallet->balance);
    }
}
