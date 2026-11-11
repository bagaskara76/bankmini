<?php

namespace Database\Seeders;

use App\Models\PendingMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class PendingMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            ['name' => 'Muhammad Rizki',       'email' => 'rizki@gmail.com',    'grade' => 10, 'kelas' => 'RPL 1'],
            ['name' => 'Siti Nurhaliza',        'email' => 'siti@gmail.com',     'grade' => 10, 'kelas' => 'AK 1'],
            ['name' => 'Rendi Firmansyah',      'email' => 'rendi@gmail.com',    'grade' => 11, 'kelas' => 'TKJ 1'],
            ['name' => 'Aulia Rahma',           'email' => 'aulia@gmail.com',    'grade' => 11, 'kelas' => 'DKV 1'],
            ['name' => 'Dimas Ardiansyah',      'email' => null,                 'grade' => 10, 'kelas' => 'RPL 2'],
            ['name' => 'Nadia Putri',           'email' => 'nadia@gmail.com',    'grade' => 12, 'kelas' => 'AK 3'],
            ['name' => 'Yoga Pratama',          'email' => null,                 'grade' => 11, 'kelas' => 'MP 1'],
            ['name' => 'Rina Wulandari',        'email' => 'rina@gmail.com',     'grade' => 10, 'kelas' => 'LPS 1'],
            ['name' => 'Hafid Maulana',         'email' => 'hafid@gmail.com',    'grade' => 12, 'kelas' => 'RPL 2'],
            ['name' => 'Zahra Anggraini',       'email' => null,                 'grade' => 11, 'kelas' => 'AK 2'],
            ['name' => 'Bagas Kurniawan',       'email' => 'bagas@gmail.com',    'grade' => 10, 'kelas' => 'TKJ 2'],
            ['name' => 'Mita Oktaviani',        'email' => 'mita@gmail.com',     'grade' => 12, 'kelas' => 'BR 1'],
        ];

        foreach ($members as $m) {
            // Hindari duplikasi email jika sudah ada di pending_members atau users
            if ($m['email'] && (PendingMember::where('email', $m['email'])->exists() || User::where('email', $m['email'])->exists())) {
                continue;
            }

            if (PendingMember::where('name', $m['name'])->where('grade', $m['grade'])->where('kelas', $m['kelas'])->exists()) {
                continue;
            }

            PendingMember::create($m);
        }
    }
}
