<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\DestinationTicketBooking;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_only_own_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Notification::create(['user_id' => $user->id, 'title' => 'Milikku', 'message' => 'a', 'type' => 'system_alert']);
        Notification::create(['user_id' => $other->id, 'title' => 'Milik orang', 'message' => 'b', 'type' => 'system_alert']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Milikku');
        $response->assertJsonMissing(['title' => 'Milik orang']);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $notification = Notification::create([
            'user_id' => $other->id,
            'title' => 'Rahasia',
            'message' => 'x',
            'type' => 'system_alert',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/notifications/{$notification->id}/read");

        $response->assertForbidden();
        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_read' => false]);
    }

    public function test_user_cannot_delete_other_users_notification(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $notification = Notification::create([
            'user_id' => $other->id,
            'title' => 'Rahasia',
            'message' => 'x',
            'type' => 'system_alert',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    public function test_unread_count_is_correct(): void
    {
        $user = User::factory()->create();

        Notification::create(['user_id' => $user->id, 'title' => 'a', 'message' => 'a', 'type' => 'system_alert']);
        Notification::create(['user_id' => $user->id, 'title' => 'b', 'message' => 'b', 'type' => 'system_alert']);
        Notification::create([
            'user_id' => $user->id,
            'title' => 'c',
            'message' => 'c',
            'type' => 'system_alert',
            'is_read' => true,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/notifications/unread-count');

        $response->assertOk();
        $response->assertJson(['unread_count' => 2]);
    }

    public function test_mark_all_as_read_updates_all_user_notifications(): void
    {
        $user = User::factory()->create();

        Notification::create(['user_id' => $user->id, 'title' => 'a', 'message' => 'a', 'type' => 'system_alert']);
        Notification::create(['user_id' => $user->id, 'title' => 'b', 'message' => 'b', 'type' => 'system_alert']);

        $this->actingAs($user, 'sanctum')->putJson('/api/notifications/read-all')->assertOk();

        $this->assertEquals(0, Notification::forUser($user->id)->unread()->count());
    }

    public function test_booking_confirmation_creates_notification_for_tourist_and_manager(): void
    {
        $tourist = User::factory()->create();
        $manager = User::factory()->manager()->create();
        $destination = Destination::factory()->create(['manager_id' => $manager->id]);

        $booking = Booking::create([
            'user_id' => $tourist->id,
            'booking_type' => 'destination_ticket',
            'status' => 'paid',
            'total_price' => 100000,
            'coin_amount' => 50,
        ]);

        DestinationTicketBooking::create([
            'booking_id' => $booking->id,
            'destination_id' => $destination->id,
            'visit_date' => now()->addDays(3)->toDateString(),
            'number_of_visitors' => 2,
            'visitor_names' => ['Budi', 'Sari'],
            'contact_person' => 'Budi',
            'contact_phone' => '081234567890',
            'status' => 'confirmed',
        ]);

        Notification::createBookingConfirmation($booking);
        Notification::createNewBookingForManager($booking);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $tourist->id,
            'type' => 'booking_confirmed',
            'notifiable_id' => $booking->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $manager->id,
            'type' => 'new_booking',
            'notifiable_id' => $booking->id,
        ]);
    }

    public function test_review_store_triggers_new_review_notification_for_destination_manager(): void
    {
        $tourist = User::factory()->create();
        $manager = User::factory()->manager()->create();
        $destination = Destination::factory()->create(['manager_id' => $manager->id]);

        $response = $this->actingAs($tourist, 'sanctum')->postJson('/api/reviews', [
            'reviewable_type' => 'Destination',
            'reviewable_id' => $destination->id,
            'rating' => 5,
            'comment' => 'Keren banget!',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $manager->id,
            'type' => 'new_review',
        ]);
    }

    public function test_review_store_skips_notification_when_destination_has_no_manager(): void
    {
        $tourist = User::factory()->create();
        $destination = Destination::factory()->create(['manager_id' => null]);

        $this->actingAs($tourist, 'sanctum')->postJson('/api/reviews', [
            'reviewable_type' => 'Destination',
            'reviewable_id' => $destination->id,
            'rating' => 4,
            'comment' => 'Bagus',
        ])->assertCreated();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_order_received_helper_notifies_umkm_owner(): void
    {
        $owner = User::factory()->umkm()->create();
        $umkm = \App\Models\Umkm::factory()->create(['user_id' => $owner->id]);
        $tourist = User::factory()->create();

        $order = Order::create([
            'user_id' => $tourist->id,
            'umkm_id' => $umkm->id,
            'total_price' => 50000,
            'status' => OrderStatus::PAID,
            'payment_method' => PaymentMethod::COIN,
            'coin_amount' => 25,
            'coin_to_rupiah_rate' => 2000,
            'rupiah_equivalent' => 50000,
        ]);

        Notification::createOrderReceived($order);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $owner->id,
            'type' => 'new_order',
            'notifiable_id' => $order->id,
        ]);
    }

    public function test_register_with_referral_code_notifies_code_owner(): void
    {
        $referrer = User::factory()->create([
            'referral_code' => 'CNQOL8VM',
        ]);

        $response = $this->actingAs($referrer, 'sanctum')->postJson('/api/register', [
            'name' => 'Budi Baru',
            'email' => 'budi.baru@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'referral_code' => 'CNQOL8VM',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $referrer->id,
            'type' => 'referral_registered',
        ]);
    }

    public function test_register_without_referral_code_does_not_notify(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Solo User',
            'email' => 'solo.user@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertStatus(201);

        $this->assertDatabaseCount('notifications', 0);
    }
}