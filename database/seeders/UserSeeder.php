<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('👤 Membuat users...');

        $now = now();

        $users = [
            // Admin
            ['email' => 'admin@ejt.com',      'name' => 'Admin EJT',           'role' => 'admin',    'status' => 'active'],
            ['email' => 'superadmin@ejt.com',  'name' => 'Super Admin EJT',     'role' => 'admin',    'status' => 'active'],

            // Manager
            ['email' => 'manager@ejt.com',     'name' => 'Manager Jatim',       'role' => 'manager',  'status' => 'active'],
            ['email' => 'manager2@ejt.com',    'name' => 'Manager Malang',      'role' => 'manager',  'status' => 'active'],
            ['email' => 'manager3@ejt.com',    'name' => 'Manager Banyuwangi',  'role' => 'manager',  'status' => 'active'],

            // UMKM
            ['email' => 'umkm@ejt.com',        'name' => 'UMKM Demo EJT',       'role' => 'umkm',     'status' => 'active'],
            ['email' => 'umkm2@ejt.com',       'name' => 'UMKM Malang',         'role' => 'umkm',     'status' => 'active'],
            ['email' => 'umkm3@ejt.com',       'name' => 'UMKM Batu',           'role' => 'umkm',     'status' => 'active'],
            ['email' => 'umkm4@ejt.com',       'name' => 'UMKM Surabaya',       'role' => 'umkm',     'status' => 'active'],
            ['email' => 'umkm5@ejt.com',       'name' => 'UMKM Banyuwangi',     'role' => 'umkm',     'status' => 'active'],

            // Tourist
            ['email' => 'tourist@ejt.com',     'name' => 'Tourist Test',         'role' => 'tourist',  'status' => 'active'],
            ['email' => 'andi@gmail.com',      'name' => 'Andi Prasetyo',        'role' => 'tourist',  'status' => 'active'],
            ['email' => 'budi@gmail.com',      'name' => 'Budi Santoso',         'role' => 'tourist',  'status' => 'active'],
            ['email' => 'citra@gmail.com',     'name' => 'Citra Dewi',           'role' => 'tourist',  'status' => 'active'],
            ['email' => 'dina@gmail.com',      'name' => 'Dina Rahmawati',       'role' => 'tourist',  'status' => 'active'],
            ['email' => 'eko@gmail.com',       'name' => 'Eko Widodo',           'role' => 'tourist',  'status' => 'active'],
            ['email' => 'fani@gmail.com',      'name' => 'Fani Amelia',          'role' => 'tourist',  'status' => 'active'],
            ['email' => 'galih@gmail.com',     'name' => 'Galih Nugroho',        'role' => 'tourist',  'status' => 'active'],
            ['email' => 'helen@gmail.com',     'name' => 'Helen Sari',           'role' => 'tourist',  'status' => 'active'],
            ['email' => 'irfan@gmail.com',     'name' => 'Irfan Hakim',          'role' => 'tourist',  'status' => 'active'],
            ['email' => 'joko@gmail.com',      'name' => 'Joko Susilo',          'role' => 'tourist',  'status' => 'active'],
            ['email' => 'karti@gmail.com',     'name' => 'Kartika Sari',         'role' => 'tourist',  'status' => 'active'],
        ];

        $created = 0;

        foreach ($users as $u) {
            $existing = DB::table('users')->where('email', $u['email'])->first();
            if (!$existing) {
                $id = DB::table('users')->insertGetId([
                    'name'              => $u['name'],
                    'email'             => $u['email'],
                    'password'          => Hash::make('password123'),
                    'phone'             => '081' . rand(200000000, 899999999),
                    'role'              => $u['role'],
                    'status'            => $u['status'],
                    'loyalty_tier'      => 'bronze',
                    'referral_code'     => strtoupper(Str::random(8)),
                    'email_verified_at' => $now,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);

                DB::table('wallets')->insertOrIgnore([
                    'user_id'    => $id,
                    'balance'    => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $created++;
            }
        }

        // Give tourists wallet balance
        $tourists = DB::table('users')->where('role', 'tourist')->get();
        foreach ($tourists as $t) {
            $hasBalance = DB::table('wallets')->where('user_id', $t->id)->where('balance', '>', 0)->exists();
            if (!$hasBalance) {
                DB::table('wallets')->where('user_id', $t->id)->update(['balance' => rand(500, 5000)]);
            }
        }

        $this->command->info("  ✅ {$created} users baru + wallets");
    }
}
