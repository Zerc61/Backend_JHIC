<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\ItineraryDay;
use App\Models\ItineraryItem;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        return $user;
    }

    private function makeDestination(float $lat, float $lng, int $ticketPrice = 25000): Destination
    {
        return Destination::factory()->create([
            'latitude' => $lat,
            'longitude' => $lng,
            'ticket_price' => $ticketPrice,
            'status' => 'published',
        ]);
    }

    private function createPlan(User $user, array $overrides = []): TripPlan
    {
        return TripPlan::create(array_merge([
            'user_id'       => $user->id,
            'title'         => 'Trip Test',
            'start_date'    => '2026-09-01',
            'end_date'      => '2026-09-02',
            'budget'        => 1000000,
            'duration_days' => 2,
            'total_people'  => 2,
            'estimated_cost'=> 0,
            'is_public'     => false,
        ], $overrides));
    }

    private function makeDays(TripPlan $plan): void
    {
        ItineraryDay::create(['trip_plan_id' => $plan->id, 'date' => '2026-09-01', 'sort_order' => 0]);
        ItineraryDay::create(['trip_plan_id' => $plan->id, 'date' => '2026-09-02', 'sort_order' => 1]);
    }

    public function test_create_plan_with_date_range_builds_days(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/trip-plans', [
                'title'       => 'Liburan DIY',
                'start_date'  => '2026-09-05',
                'end_date'    => '2026-09-08',
                'total_people'=> 2,
                'budget'      => 2000000,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Liburan DIY')
            ->assertJsonPath('data.duration_days', 4)
            ->assertJsonCount(4, 'data.days');

        $this->assertDatabaseCount('itinerary_days', 4);
    }

    public function test_store_supports_legacy_destinations_payload(): void
    {
        $user = $this->makeUser();
        $dest = $this->makeDestination(-7.8, 110.35, 50000);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/trip-plans', [
                'title'          => 'Trip Legacy',
                'duration_days'  => 2,
                'total_people'   => 1,
                'destinations'   => [
                    ['id' => $dest->id, 'day_number' => 1, 'sort_order' => 0],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.itinerary.day_1.0.destination_name', $dest->name);

        $this->assertDatabaseHas('trip_plan_destinations', [
            'destination_id' => $dest->id,
            'day_number' => 1,
        ]);
    }

    public function test_item_cost_scales_with_people_count(): void
    {
        $user = $this->makeUser();
        $plan = $this->createPlan($user, ['total_people' => 3]);
        $this->makeDays($plan);
        $day = $plan->days()->first();
        $dest = $this->makeDestination(-7.8, 110.35, 25000);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/itinerary-days/' . $day->id . '/items', [
                'slot'         => 'morning',
                'type'         => 'destination',
                'reference_id' => $dest->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.estimated_cost', 75000);

        $this->assertSame(75000.0, (float) $plan->fresh()->estimated_cost);
    }

    public function test_non_owner_cannot_access_plan(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $plan = $this->createPlan($owner);

        $this->actingAs($intruder, 'sanctum')
            ->getJson('/api/trip-plans/' . $plan->id)
            ->assertStatus(403);
    }

    public function test_reorder_items_updates_slot_and_order(): void
    {
        $user = $this->makeUser();
        $plan = $this->createPlan($user);
        $this->makeDays($plan);
        $day = $plan->days()->first();

        $a = ItineraryItem::create(['day_id' => $day->id, 'slot' => 'morning', 'type' => 'custom', 'name' => 'A', 'sort_order' => 0]);
        $b = ItineraryItem::create(['day_id' => $day->id, 'slot' => 'morning', 'type' => 'custom', 'name' => 'B', 'sort_order' => 1]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/itinerary-days/' . $day->id . '/reorder', [
                'items' => [
                    ['id' => $a->id, 'slot' => 'morning', 'sort_order' => 1],
                    ['id' => $b->id, 'slot' => 'morning', 'sort_order' => 0],
                ],
            ])
            ->assertStatus(200);

        $this->assertSame(1, (int) $a->fresh()->sort_order);
        $this->assertSame(0, (int) $b->fresh()->sort_order);
    }

    public function test_optimize_day_sorts_by_nearest_neighbor(): void
    {
        $user = $this->makeUser();
        $plan = $this->createPlan($user);
        $this->makeDays($plan);
        $day = $plan->days()->first();

        $d1 = $this->makeDestination(-7.80, 110.35);
        $d2 = $this->makeDestination(-7.82, 110.37);
        $d3 = $this->makeDestination(-7.95, 110.50);

        $i1 = ItineraryItem::create(['day_id' => $day->id, 'slot' => 'afternoon', 'type' => 'destination', 'name' => 'Jauh', 'reference_id' => $d3->id, 'lat' => -7.95, 'lng' => 110.50, 'sort_order' => 0]);
        $i2 = ItineraryItem::create(['day_id' => $day->id, 'slot' => 'afternoon', 'type' => 'destination', 'name' => 'Dekat1', 'reference_id' => $d1->id, 'lat' => -7.80, 'lng' => 110.35, 'sort_order' => 1]);
        $i3 = ItineraryItem::create(['day_id' => $day->id, 'slot' => 'afternoon', 'type' => 'destination', 'name' => 'Dekat2', 'reference_id' => $d2->id, 'lat' => -7.82, 'lng' => 110.37, 'sort_order' => 2]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/itinerary-days/' . $day->id . '/optimize')
            ->assertStatus(200);

        $ordered = $day->fresh()->items()->orderBy('sort_order')->pluck('name')->all();

        $this->assertSame(['Dekat1', 'Dekat2', 'Jauh'], $ordered);
    }

    public function test_share_generates_token_and_public_read_is_visible(): void
    {
        $user = $this->makeUser();
        $plan = $this->createPlan($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/trip-plans/' . $plan->id . '/share')
            ->assertStatus(200)
            ->assertJsonPath('is_public', true);

        $token = $plan->fresh()->share_token;
        $this->assertNotEmpty($token);

        $this->getJson('/api/shared/trip/' . $token)
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Trip Test');

        $this->getJson('/api/shared/trip/trp_unknown')
            ->assertStatus(404);
    }

    public function test_delete_item_recalculates_plan_cost(): void
    {
        $user = $this->makeUser();
        $plan = $this->createPlan($user);
        $this->makeDays($plan);
        $day = $plan->days()->first();

        $item = ItineraryItem::create(['day_id' => $day->id, 'slot' => 'morning', 'type' => 'custom', 'name' => 'Wisata', 'estimated_cost' => 100000, 'sort_order' => 0]);
        $plan->forceFill(['estimated_cost' => 100000])->save();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/itinerary-items/' . $item->id)
            ->assertStatus(200);

        $this->assertSame(0.0, (float) $plan->fresh()->estimated_cost);
        $this->assertDatabaseMissing('itinerary_items', ['id' => $item->id]);
    }
}