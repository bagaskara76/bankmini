<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $kelas = [
            ['grade' => 10, 'kelas' => 'RPL 1'],
            ['grade' => 10, 'kelas' => 'RPL 2'],
            ['grade' => 10, 'kelas' => 'AK 1'],
            ['grade' => 11, 'kelas' => 'AK 2'],
            ['grade' => 11, 'kelas' => 'TKJ 1'],
            ['grade' => 11, 'kelas' => 'DKV 1'],
            ['grade' => 12, 'kelas' => 'RPL 2'],
            ['grade' => 12, 'kelas' => 'AK 3'],
            ['grade' => 12, 'kelas' => 'MP 1'],
            ['grade' => 10, 'kelas' => 'LPS 1'],
            ['grade' => 11, 'kelas' => 'BR 1'],
            ['grade' => 12, 'kelas' => 'AK 4'],
        ];

        $balances = [
            50000, 75000, 30000, 100000, 25000, 60000,
            80000, 45000, 90000, 35000, 120000, 55000,
        ];

        $customerUsers = User::where('role', 'customer')->orderBy('id')->get();
        $year = date('Y');

        foreach ($customerUsers as $i => $user) {
            // Cek apakah customer sudah ada (termasuk yang soft-deleted)
            $existingCustomer = Customer::withTrashed()->where('user_id', $user->id)->first();
            if ($existingCustomer) {
                if ($existingCustomer->trashed()) {
                    $existingCustomer->restore();
                }
                continue;
            }

            $kelasData = $kelas[$i % count($kelas)];
            $balance   = $balances[$i % count($balances)];

            // Cari nomor rekening unik yang belum pernah dipakai di tabel (termasuk yang soft-deleted)
            $seq = (int) $user->id;
            do {
                $accountNo = $year . str_pad($seq, 6, '0', STR_PAD_LEFT);
                $seq++;
            } while (Customer::withTrashed()->where('account_number', $accountNo)->orWhere('qr_code_string', $accountNo)->exists());

            Customer::create([
                'user_id'        => $user->id,
                'account_number' => $accountNo,
                'pin'            => Hash::make('123456'),
                'qr_code_string' => $accountNo,
                'balance'        => $balance,
                'grade'          => $kelasData['grade'],
                'kelas'          => $kelasData['kelas'],
                'is_graduated'   => false,
            ]);
        }
    }
}
