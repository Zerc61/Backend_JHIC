<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Notification;
use App\Models\PriceHistory;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WishlistCollection;
use App\Models\WishlistItem;
use App\Services\PriceTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private WishlistItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Wallet::create(['user_id' => $this->user->id, 'balance' => 0]);

        $collection = WishlistCollection::create(['user_id' => $this->user->id, 'name' => 'Tracks', 'is_default' => false]);

        $dest = Destination::factory()->create([
            'ticket_price' => 100000,
            'status' => 'published',
        ]);

        $this->item = WishlistItem::create([
            'collection_id' => $collection->id,
            'wishlistable_type' => Destination::class,
            'wishlistable_id' => $dest->id,
        ]);
    }

    private function setTicketPrice(float $price): void
    {
        $dest = Destination::find($this->item->wishlistable_id);
        $dest->forceFill(['ticket_price' => $price])->save();
    }

    private function track(): \App\Services\PriceTrackingService
    {
        return app(PriceTrackingService::class)->trackItem($this->item->fresh());
    }

    public function test_price_drop_of_20_percent_fires_notification_once(): void
    {
        $this->track(); // snapshot 100000

        $this->setTicketPrice(80000);
        $result = $this->track();
        $this->assertSame(1, $result['drops']);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'price_drop',
        ]);

        // Throttle 24 jam: run lagi → tidak menambah notif baru
        $this->track();
        $count = Notification::where('user_id', $this->user->id)->where('type', 'price_drop')->count();
        $this->assertSame(1, $count);
    }

    public function test_price_up_or_flat_does_not_fire_notification(): void
    {
        $this->track();
        $this->setTicketPrice(120000);
        $result = $this->track();

        $this->assertSame(0, $result['drops']);
        $this->assertSame(0, $result['targets']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->user->id, 'type' => 'price_drop']);
    }

    public function test_target_price_reached_fires_notification(): void
    {
        $this->item->forceFill(['target_price' => 90000])->save();

        $this->track(); // 100000 > target

        $this->setTicketPrice(90000);
        $result = $this->track();
        $this->assertSame(1, $result['targets']);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'price_target',
        ]);
    }

    public function test_small_drop_below_5_percent_is_ignored(): void
    {
        $this->track();
        $this->setTicketPrice(96000); // -4%
        $result = $this->track();

        $this->assertSame(0, $result['drops']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->user->id, 'type' => 'price_drop']);
    }

    public function test_history_pruned_to_maximum_points(): void
    {
        $service = app(PriceTrackingService::class);

        for ($i = 1; $i <= 65; $i++) {
            $this->setTicketPrice(100000 + $i);
            $service->trackItem($this->item->fresh());
        }

        $count = PriceHistory::where('wishlist_item_id', $this->item->id)->count();
        $this->assertSame(60, $count);
    }
}