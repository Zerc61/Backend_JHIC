<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pindahkan data wishlists lama (polimorfik) ke koleksi "Default" per user.
     */
    public function up(): void
    {
        $oldWishlists = DB::table('wishlists')
            ->orderBy('user_id')
            ->orderBy('id')
            ->get();

        if ($oldWishlists->isEmpty()) {
            return;
        }

        foreach ($oldWishlists->groupBy('user_id') as $userId => $rows) {
            $collectionId = DB::table('wishlist_collections')->insertGetId([
                'user_id' => $userId,
                'name' => 'Default',
                'description' => null,
                'is_default' => true,
                'is_public' => false,
                'share_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($rows as $row) {
                DB::table('wishlist_items')->insertOrIgnore([
                    'collection_id' => $collectionId,
                    'wishlistable_type' => $row->wishlistable_type,
                    'wishlistable_id' => $row->wishlistable_id,
                    'target_price' => null,
                    'note' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('wishlist_collections')->where('is_default', true)->delete();
    }
};