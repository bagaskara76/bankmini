<?php

namespace Database\Seeders;

use App\Models\RecoveryOtp;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class RecoveryOtpSeeder extends Seeder
{
    public function run(): void
    {
        $customerUsers = User::where('role', 'customer')->take(10)->get();

        $purposes = ['pin', 'password'];

        foreach ($customerUsers as $i => $user) {
            $purpose = $purposes[$i % count($purposes)];
            $isConsumed = ($i % 2 === 0);

            RecoveryOtp::create([
                'user_id'          => $user->id,
                'purpose'          => $purpose,
                'otp_hash'         => Hash::make('123456'),
                'expires_at'       => Carbon::now()->addMinutes(15),
                'consumed_at'      => $isConsumed ? Carbon::now()->subMinutes(5) : null,
                'attempts'         => $isConsumed ? 1 : 0,
                'verified_at'      => $isConsumed ? Carbon::now()->subMinutes(6) : null,
                'reset_token_hash' => $isConsumed ? hash('sha256', 'sample_token_' . $i) : null,
                'created_at'       => Carbon::now()->subMinutes(10),
                'updated_at'       => Carbon::now()->subMinutes(5),
            ]);
        }
    }
}
