<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Teller 1',
            'username' => 'teller',
            'password' => bcrypt('password'),
            'role' => 'teller',
        ]);

        $customerUser = \App\Models\User::factory()->create([
            'name' => 'Nasabah Dummy',
            'username' => 'nasabah1',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        \App\Models\Customer::create([
            'user_id' => $customerUser->id,
            'account_number' => '2026001',
            'pin' => bcrypt('123456'),
            'qr_code_string' => 'QR-NASABAH1-2026',
            'balance' => 50000,
        ]);
    }
}
