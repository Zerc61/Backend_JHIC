<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Product;
use App\Models\Review;
use App\Models\TransportTicket;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Wallet::create(['user_id' => $this->user->id, 'balance' => 0]);
    }

    private function makeDestination(string $name, int $price, string $slug): Destination
    {
        return Destination::create([
            'manager_id' => $this->user->id,
            'name' => $name,
            'slug' => $slug,
            'description' => "Destinasi {$name} di Lombok.",
            'address' => 'Jl. Contoh',
            'latitude' => -8.9,
            'longitude' => 116.3,
            'ticket_price' => $price,
            'status' => 'published',
        ]);
    }

    private function makeHotel(string $name, int $minPrice): Hotel
    {
        $destination = $this->makeDestination('Pantai Kuta Search', 15000, 'pantai-kuta-search');
        $hotel = Hotel::create([
            'manager_id' => $this->user->id,
            'destination_id' => $destination->id,
            'name' => $name,
            'slug' => 'kuta-search-resort',
            'description' => 'Resort dekat pantai.',
            'star_rating' => 4,
            'status' => 'published',
        ]);
        \App\Models\HotelRoom::create([
            'hotel_id' => $hotel->id,
            'name' => 'Deluxe',
            'price_per_night' => $minPrice,
            'total_rooms' => 10,
            'status' => 'available',
        ]);

        return $hotel;
    }

    private function makeProduct(string $name, int $price): Product
    {
        $category = \App\Models\UmkmCategory::create(['name' => 'Kuliner']);
        $umkm = Umkm::create([
            'user_id' => $this->user->id,
            'destination_id' => Destination::first()->id,
            'umkm_category_id' => $category->id,
            'name' => 'Warung Kuta Search',
            'slug' => 'warung-kuta-search',
            'status' => 'active',
        ]);

        return Product::create([
            'umkm_id' => $umkm->id,
            'name' => $name,
            'slug' => 'kopi-' . strtolower(str_replace(' ', '-', $name)),
            'description' => 'Produk khas',
            'price' => $price,
            'stock' => 50,
            'unit' => 'pcs',
            'status' => 'available',
        ]);
    }

    private function makeEvent(string $title): Event
    {
        return Event::create([
            'destination_id' => Destination::first()->id,
            'created_by' => $this->user->id,
            'title' => $title,
            'slug' => 'event-' . strtolower(str_replace(' ', '-', $title)),
            'description' => 'Event seru',
            'start_date' => now(),
            'end_date' => now()->addDays(2),
            'location' => 'Lombok',
            'status' => 'upcoming',
        ]);
    }

    public function test_search_returns_matching_entities_across_types(): void
    {
        $this->makeDestination('Pantai Kuta Search', 15000, 'pantai-kuta-search');
        $this->makeHotel('Kuta Search Resort', 450000);
        $this->makeProduct('Kopi Search Premium', 45000);
        $this->makeEvent('Festival Kuta Search');

        $response = $this->getJson('/api/search?q=kuta+search')->assertOk();

        $types = collect($response->json('data'))->pluck('type')->all();
        $this->assertContains('destination', $types);
        $this->assertContains('hotel', $types);
        $this->assertContains('umkm', $types);
        $this->assertContains('event', $types);
        $this->assertSame(4, $response->json('meta.total'));
    }

    public function test_types_filter_limits_results(): void
    {
        $this->makeDestination('Pantai Kuta Search', 15000, 'pantai-kuta-search');
        $this->makeProduct('Kopi Search Premium', 45000);

        $this->getJson('/api/search?q=kuta+search&types=destination')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'destination')
            ->assertJsonPath('meta.types', ['destination']);
    }

    public function test_price_and_rating_filters(): void
    {
        $dest = $this->makeDestination('Pantai Kuta Search', 15000, 'pantai-kuta-search');
        $this->makeProduct('Kopi Search Premium', 45000);

        Review::create([
            'user_id' => $this->user->id,
            'reviewable_type' => Destination::class,
            'reviewable_id' => $dest->id,
            'rating' => 5,
            'comment' => 'Keren',
        ]);

        // Hanya destinasi (harga 15.000) yang lolos min_price=15000 + rating>=4.5
        $this->getJson('/api/search?q=kuta+search&min_price=15000&rating=4.5')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'destination')
            ->assertJsonPath('data.0.rating', 5);

        // Produk (45.000) di atas max_price=20000 → tidak tampil
        $this->getJson('/api/search?q=kuta+search&max_price=20000')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', 'destination');
    }

    public function test_sort_by_price_asc_and_desc(): void
    {
        $this->makeDestination('Pantai Kuta Search', 15000, 'pantai-kuta-search');
        $this->makeHotel('Kuta Search Resort', 450000);
        $this->makeProduct('Kopi Search Premium', 45000);

        $asc = collect($this->getJson('/api/search?q=kuta+search&sort=price_asc')->json('data'))->pluck('price');
        $this->assertSame($asc.sort()->values()->all(), $asc->all());

        $desc = collect($this->getJson('/api/search?q=kuta+search&sort=price_desc')->json('data'))->pluck('price');
        $this->assertSame($desc.sortDesc()->values()->all(), $desc->all());
    }

    public function test_radius_filter_keeps_only_nearby_geolocated_entities(): void
    {
        $this->makeDestination('Pantai Kuta Search', 15000, 'pantai-kuta-search');
        $this->makeHotel('Kuta Search Resort', 450000);
        $this->makeProduct('Kopi Search Premium', 45000); // via umkm tanpa koordinat → tidak geo
        $this->makeEvent('Festival Kuta Search');           // tanpa koordinat

        $this->getJson('/api/search?q=kuta+search&lat=-8.9&lng=116.3&radius=5&sort=nearest')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'destination')
            ->assertJsonPath('data.1.type', 'hotel');
    }

    public function test_hotel_price_is_minimum_room_price(): void
    {
        $this->makeHotel('Kuta Search Resort', 375000);

        $this->getJson('/api/search?q=kuta+search&types=hotel')
            ->assertOk()
            ->assertJsonPath('data.0.price', 375000)
            ->assertJsonPath('data.0.price_formatted', 'Rp 375.000');
    }
}