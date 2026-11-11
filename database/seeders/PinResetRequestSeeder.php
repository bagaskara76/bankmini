<?php

namespace Database\Seeders;

use App\Models\PinResetRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PinResetRequestSeeder extends Seeder
{
    public function run(): void
    {
        $customerUsers = User::where('role', 'customer')->take(10)->get();

        $statuses = ['completed', 'approved', 'pending'];

        foreach ($customerUsers as $i => $user) {
            $status = $statuses[$i % count($statuses)];
            $approvedAt = in_array($status, ['approved', 'completed']) ? Carbon::now()->subDays(10 - $i) : null;

            PinResetRequest::create([
                'user_id'     => $user->id,
                'username'    => $user->username,
                'status'      => $status,
                'approved_at' => $approvedAt,
                'created_at'  => Carbon::now()->subDays(11 - $i),
                'updated_at'  => Carbon::now()->subDays(10 - $i),
            ]);
        }
    }
}
