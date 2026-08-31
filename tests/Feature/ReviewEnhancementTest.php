<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewEnhancementTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithWallet(): User
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        return $user->fresh();
    }

    public function test_store_review_with_photos_and_video_earns_photo_reward(): void
    {
        Storage::fake('public');
        $tourist = $this->createUserWithWallet();
        $destination = Destination::factory()->create(['manager_id' => User::factory()->manager()->create()->id]);

        $response = $this->actingAs($tourist, 'sanctum')->post('/api/reviews', [
            'reviewable_type' => 'Destination',
            'reviewable_id' => $destination->id,
            'rating' => 5,
            'comment' => 'Mantap sekali!',
            'video_url' => 'https://www.youtube.com/watch?v=abc123',
            'photos' => [
                UploadedFile::fake()->image('foto1.jpg'),
                UploadedFile::fake()->image('foto2.jpg'),
            ],
        ]);

        $response->assertCreated();
        $data = $response->json('data');

        $this->assertCount(2, $data['photos']);
        $this->assertStringContainsString('/storage/reviews/', $data['photos'][0]);
        $this->assertEquals('https://www.youtube.com/watch?v=abc123', $data['video_url']);

        $this->assertDatabaseHas('loyalty_rewards', [
            'user_id' => $tourist->id,
            'reward_key' => 'review_photo_' . $data['id'],
        ]);
        $this->assertEquals(150, (float) $tourist->fresh()->wallet->balance);
    }

    public function test_store_review_without_media_only_earns_base_review(): void
    {
        $tourist = $this->createUserWithWallet();
        $destination = Destination::factory()->create(['manager_id' => User::factory()->manager()->create()->id]);

        $response = $this->actingAs($tourist, 'sanctum')->postJson('/api/reviews', [
            'reviewable_type' => 'Destination',
            'reviewable_id' => $destination->id,
            'rating' => 4,
            'comment' => 'Bagus',
        ]);

        $response->assertCreated();
        $this->assertEquals(50, (float) $tourist->fresh()->wallet->balance);
    }

    public function test_user_cannot_vote_own_review(): void
    {
        $tourist = $this->createUserWithWallet();
        $destination = Destination::factory()->create();
        $review = Review::create([
            'user_id' => $tourist->id,
            'reviewable_type' => Destination::class,
            'reviewable_id' => $destination->id,
            'rating' => 5,
            'comment' => 'Keren',
        ]);

        $response = $this->actingAs($tourist, 'sanctum')
            ->postJson("/api/reviews/{$review->id}/vote");

        $response->assertStatus(422);
    }

    public function test_helpful_vote_toggles_count(): void
    {
        $author = $this->createUserWithWallet();
        $voter = $this->createUserWithWallet();
        $destination = Destination::factory()->create();

        $review = Review::create([
            'user_id' => $author->id,
            'reviewable_type' => Destination::class,
            'reviewable_id' => $destination->id,
            'rating' => 5,
            'comment' => 'Keren',
        ]);

        $first = $this->actingAs($voter, 'sanctum')->postJson("/api/reviews/{$review->id}/vote");
        $first->assertStatus(201);
        $first->assertJson(['helpful_count' => 1, 'voted_by_me' => true]);

        $second = $this->actingAs($voter, 'sanctum')->postJson("/api/reviews/{$review->id}/vote");
        $second->assertOk();
        $second->assertJson(['helpful_count' => 0, 'voted_by_me' => false]);

        $this->assertDatabaseCount('review_votes', 0);
    }

    public function test_manager_can_respond_and_author_is_notified(): void
    {
        $tourist = $this->createUserWithWallet();
        $manager = User::factory()->manager()->create();
        $destination = Destination::factory()->create(['manager_id' => $manager->id]);

        $review = Review::create([
            'user_id' => $tourist->id,
            'reviewable_type' => Destination::class,
            'reviewable_id' => $destination->id,
            'rating' => 4,
            'comment' => 'Lumayan',
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson("/api/reviews/{$review->id}/respond", [
                'response_text' => 'Terima kasih atas masukannya!',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.response.text', 'Terima kasih atas masukannya!');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'response_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $tourist->id,
            'type' => 'review_response',
            'notifiable_id' => $review->id,
        ]);
    }

    public function test_unrelated_manager_cannot_respond(): void
    {
        $destination = Destination::factory()->create(['manager_id' => User::factory()->manager()->create()->id]);
        $otherManager = User::factory()->manager()->create();
        $review = Review::create([
            'user_id' => $this->createUserWithWallet()->id,
            'reviewable_type' => Destination::class,
            'reviewable_id' => $destination->id,
            'rating' => 5,
            'comment' => 'Bagus',
        ]);

        $this->actingAs($otherManager, 'sanctum')
            ->postJson("/api/reviews/{$review->id}/respond", ['response_text' => 'Halo'])
            ->assertForbidden();
    }

    public function test_manager_mine_lists_only_own_entity_reviews(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $destination = Destination::factory()->create(['manager_id' => $manager->id]);
        $otherDestination = Destination::factory()->create(['manager_id' => $otherManager->id]);

        foreach ([$destination, $otherDestination] as $d) {
            Review::create([
                'user_id' => User::factory()->create()->id,
                'reviewable_type' => Destination::class,
                'reviewable_id' => $d->id,
                'rating' => 4,
                'comment' => 'Review untuk ' . $d->id,
            ]);
        }

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/reviews/mine');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.reviewable_id', $destination->id);
    }

    public function test_umkm_mine_lists_umkm_and_product_reviews(): void
    {
        $owner = User::factory()->umkm()->create();
        $umkm = Umkm::factory()->create(['user_id' => $owner->id]);
        $product = Product::factory()->create(['umkm_id' => $umkm->id]);
        $otherOwner = User::factory()->umkm()->create();
        $otherUmkm = Umkm::factory()->create(['user_id' => $otherOwner->id]);

        $tourist = $this->createUserWithWallet();
        $reviewUmkm = Review::create([
            'user_id' => $tourist->id,
            'reviewable_type' => Umkm::class,
            'reviewable_id' => $umkm->id,
            'rating' => 5,
            'comment' => 'Toko ramah',
        ]);
        $reviewProduct = Review::create([
            'user_id' => $tourist->id,
            'reviewable_type' => Product::class,
            'reviewable_id' => $product->id,
            'rating' => 4,
            'comment' => 'Produk enak',
        ]);
        Review::create([
            'user_id' => $tourist->id,
            'reviewable_type' => Umkm::class,
            'reviewable_id' => $otherUmkm->id,
            'rating' => 3,
            'comment' => 'Bukan punyaku',
        ]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/reviews/mine');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $ids = [$response->json('data.0.id'), $response->json('data.1.id')];
        $this->assertContains($reviewUmkm->id, $ids);
        $this->assertContains($reviewProduct->id, $ids);
    }

    public function test_tourist_cannot_access_mine(): void
    {
        $this->actingAs($this->createUserWithWallet(), 'sanctum')
            ->getJson('/api/reviews/mine')
            ->assertForbidden();
    }
}