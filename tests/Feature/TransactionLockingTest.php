<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransactionLockingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'testing']);
    }

    public function test_order_prevents_overselling_with_locking()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 1000,
        ]);

        $umkm = Umkm::factory()->create();
        $product = Product::factory()->create([
            'umkm_id' => $umkm->id,
            'stock' => 2,
            'price' => 50000,
            'status' => ProductStatus::AVAILABLE,
        ]);

        $this->actingAs($user);

        // Attempt to order more than available stock
        $response = $this->postJson('/api/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                ]
            ],
            'payment_method' => 'coin',
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('Stok', $response->json('message'));

        $product->refresh();
        $this->assertEquals(2, $product->stock);
    }

    public function test_order_with_sufficient_stock_succeeds()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 1000,
        ]);

        $umkm = Umkm::factory()->create();
        $product = Product::factory()->create([
            'umkm_id' => $umkm->id,
            'stock' => 10,
            'price' => 10000,
            'status' => ProductStatus::AVAILABLE,
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ],
            'payment_method' => 'coin',
        ]);

        $response->assertStatus(201);
        $product->refresh();
        $this->assertEquals(8, $product->stock);
    }

    public function test_wallet_locking_prevents_insufficient_balance_race_condition()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 10,
        ]);

        $umkm = Umkm::factory()->create();
        $product = Product::factory()->create([
            'umkm_id' => $umkm->id,
            'stock' => 100,
            'price' => 30000,
            'status' => ProductStatus::AVAILABLE,
        ]);

        $this->actingAs($user);

        // Attempt to order with insufficient balance
        // 2 x Rp 30.000 = Rp 60.000 = 30 coin > saldo 10 coin
        $response = $this->postJson('/api/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ],
            'payment_method' => 'coin',
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('Saldo coin', $response->json('message'));

        $wallet->refresh();
        $this->assertEquals(10, $wallet->balance);
    }

    public function test_wallet_balance_accurate_after_order()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 200,
        ]);

        $umkm = Umkm::factory()->create();
        $product = Product::factory()->create([
            'umkm_id' => $umkm->id,
            'stock' => 50,
            'price' => 20000,
            'status' => ProductStatus::AVAILABLE,
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ],
            'payment_method' => 'coin',
        ]);

        $response->assertStatus(201);

        $wallet->refresh();
        // 20000 Rupiah = 10 coins (rate: 1 coin = 2000 Rupiah)
        $this->assertEquals(190, $wallet->balance);
    }

    public function test_order_transaction_rolls_back_on_failure()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 500,
        ]);

        $umkm = Umkm::factory()->create();
        $product = Product::factory()->create([
            'umkm_id' => $umkm->id,
            'stock' => 5,
            'price' => 50000,
            'status' => ProductStatus::AVAILABLE,
        ]);

        $this->actingAs($user);

        // Try to order 10 when only 5 available
        $response = $this->postJson('/api/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                ]
            ],
            'payment_method' => 'coin',
        ]);

        $response->assertStatus(400);

        // Verify no order was created
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
        ]);

        // Verify stock and balance unchanged
        $product->refresh();
        $wallet->refresh();
        $this->assertEquals(5, $product->stock);
        $this->assertEquals(500, $wallet->balance);
    }

    public function test_order_cancellation_refunds_coins()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 200,
        ]);

        $umkm = Umkm::factory()->create();
        $product = Product::factory()->create([
            'umkm_id' => $umkm->id,
            'stock' => 20,
            'price' => 50000,
            'status' => ProductStatus::AVAILABLE,
        ]);

        $this->actingAs($user);

        // Create order
        $orderResponse = $this->postJson('/api/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ],
            'payment_method' => 'coin',
        ]);

        $orderId = $orderResponse['data']['id'];

        $walletAfterOrder = $wallet->fresh()->balance;
        $this->assertLessThan(200, $walletAfterOrder);

        // Cancel order
        $cancelResponse = $this->postJson("/api/orders/{$orderId}/cancel");

        $cancelResponse->assertStatus(200);

        $wallet->refresh();
        $this->assertEquals(200, $wallet->balance);
    }
}
