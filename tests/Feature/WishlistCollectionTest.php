<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Wishlist;
use App\Models\WishlistCollection;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistCollectionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        return $user;
    }

    private function makeDestination(int $price = 50000): Destination
    {
        return Destination::factory()->create([
            'ticket_price' => $price,
            'status' => 'published',
        ]);
    }

    public function test_user_can_create_and_rename_collection(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/wishlist-collections', ['name' => 'Liburan Jogja'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Liburan Jogja');

        $collection = WishlistCollection::where('user_id', $user->id)->where('name', 'Liburan Jogja')->first();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/wishlist-collections/' . $collection->id, ['name' => 'Jogja 2026'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Jogja 2026');
    }

    public function test_add_item_creates_price_snapshot(): void
    {
        $user = $this->makeUser();
        $dest = $this->makeDestination(75000);
        $collection = WishlistCollection::create(['user_id' => $user->id, 'name' => 'Favorit', 'is_default' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/wishlist-collections/{$collection->id}/items", [
                'type' => 'destination',
                'reference_id' => $dest->id,
                'target_price' => 50000,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.current_price', 75000)
            ->assertJsonPath('data.current_price_formatted', 'Rp 75.000');

        $item = WishlistItem::where('collection_id', $collection->id)->first();
        $this->assertNotNull($item);
        $this->assertDatabaseHas('price_histories', [
            'wishlist_item_id' => $item->id,
            'price' => 75000,
        ]);
    }

    public function test_non_owner_cannot_access_collection(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $collection = WishlistCollection::create(['user_id' => $owner->id, 'name' => 'Rahasia']);

        $this->actingAs($intruder, 'sanctum')
            ->getJson('/api/wishlist-collections/' . $collection->id)
            ->assertStatus(403);
    }

    public function test_default_collection_cannot_be_deleted(): void
    {
        $user = $this->makeUser();
        $collection = WishlistCollection::create(['user_id' => $user->id, 'name' => 'Default', 'is_default' => true]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/wishlist-collections/' . $collection->id)
            ->assertStatus(422);
    }

    public function test_share_collection_generates_token_and_public_read(): void
    {
        $user = $this->makeUser();
        $collection = WishlistCollection::create(['user_id' => $user->id, 'name' => 'Bagi-bagi']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/wishlist-collections/' . $collection->id . '/share')
            ->assertStatus(200)
            ->assertJsonPath('is_public', true);

        $token = $collection->fresh()->share_token;
        $this->assertNotEmpty($token);

        $this->getJson('/api/shared/wishlist/' . $token)
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Bagi-bagi');

        $this->getJson('/api/shared/wishlist/wl_unknown')
            ->assertStatus(404);
    }

    public function test_legacy_destination_wishlist_uses_default_collection(): void
    {
        $user = $this->makeUser();
        $dest = $this->makeDestination();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/wishlists', ['destination_id' => $dest->id])
            ->assertStatus(200);

        $default = WishlistCollection::where('user_id', $user->id)->where('is_default', true)->first();
        $this->assertNotNull($default);
        $this->assertDatabaseHas('wishlist_items', [
            'collection_id' => $default->id,
            'wishlistable_type' => Destination::class,
            'wishlistable_id' => $dest->id,
        ]);
        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'wishlistable_type' => Destination::class,
            'wishlistable_id' => $dest->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/wishlists/check/' . $dest->id)
            ->assertJsonPath('is_wishlisted', true);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/wishlists/' . $dest->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('wishlist_items', [
            'collection_id' => $default->id,
            'wishlistable_id' => $dest->id,
        ]);
        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'wishlistable_id' => $dest->id,
        ]);
    }

    public function test_move_item_between_collections(): void
    {
        $user = $this->makeUser();
        $dest = $this->makeDestination();
        $a = WishlistCollection::create(['user_id' => $user->id, 'name' => 'A']);
        $b = WishlistCollection::create(['user_id' => $user->id, 'name' => 'B']);

        $item = WishlistItem::create([
            'collection_id' => $a->id,
            'wishlistable_type' => Destination::class,
            'wishlistable_id' => $dest->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/wishlist-items/' . $item->id . '/move', ['collection_id' => $b->id])
            ->assertStatus(200);

        $this->assertSame($b->id, (int) $item->fresh()->collection_id);
    }
}