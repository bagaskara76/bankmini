<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin accounts (2)
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'           => 'Administrator',
                'email'          => 'admin@bankmini.sch.id',
                'password'       => Hash::make('Admin@123456'),
                'plain_password' => 'Admin@123456',
                'role'           => 'admin',
                'is_blocked'     => false,
            ]
        );

        User::firstOrCreate(
            ['username' => 'supervisor'],
            [
                'name'           => 'Supervisor Bank',
                'email'          => 'supervisor@bankmini.sch.id',
                'password'       => Hash::make('Super@123456'),
                'plain_password' => 'Super@123456',
                'role'           => 'admin',
                'is_blocked'     => false,
            ]
        );

        // Teller accounts (3)
        $tellers = [
            ['name' => 'Teller 1',     'username' => 'teller',   'email' => 'teller1@bankmini.sch.id'],
            ['name' => 'Teller 2',     'username' => 'teller2',  'email' => 'teller2@bankmini.sch.id'],
            ['name' => 'Budi Santoso', 'username' => 'budi',     'email' => 'budi@bankmini.sch.id'],
        ];

        foreach ($tellers as $t) {
            User::firstOrCreate(
                ['username' => $t['username']],
                [
                    'name'           => $t['name'],
                    'email'          => $t['email'],
                    'password'       => Hash::make('Teller@123456'),
                    'plain_password' => 'Teller@123456',
                    'role'           => 'teller',
                    'is_blocked'     => false,
                ]
            );
        }

        // Customer accounts (12 users)
        $customers = [
            ['name' => 'Agus Prasetyo',       'username' => 'agus',       'email' => 'agus@gmail.com'],
            ['name' => 'Bima Cahyadi',         'username' => 'bima',       'email' => 'bima@gmail.com'],
            ['name' => 'Citra Dewi',           'username' => 'citra',      'email' => 'citra@gmail.com'],
            ['name' => 'Dina Rahayu',          'username' => 'dina',       'email' => 'dina@gmail.com'],
            ['name' => 'Eko Purnomo',          'username' => 'eko',        'email' => 'eko@gmail.com'],
            ['name' => 'Faisal Hidayat',       'username' => 'faisal',     'email' => 'faisal@gmail.com'],
            ['name' => 'Galuh Puspita',        'username' => 'galuh',      'email' => 'galuh@gmail.com'],
            ['name' => 'Hana Lestari',         'username' => 'hana',       'email' => 'hana@gmail.com'],
            ['name' => 'Irfan Maulana',        'username' => 'irfan',      'email' => 'irfan@gmail.com'],
            ['name' => 'Joko Susanto',         'username' => 'joko',       'email' => 'joko@gmail.com'],
            ['name' => 'Kartika Sari',         'username' => 'kartika',    'email' => 'kartika@gmail.com'],
            ['name' => 'Lukman Hakim',         'username' => 'lukman',     'email' => 'lukman@gmail.com'],
        ];

        foreach ($customers as $c) {
            User::firstOrCreate(
                ['username' => $c['username']],
                [
                    'name'           => $c['name'],
                    'email'          => $c['email'],
                    'password'       => Hash::make('Nasabah@123456'),
                    'plain_password' => 'Nasabah@123456',
                    'role'           => 'customer',
                    'is_blocked'     => false,
                ]
            );
        }
    }
}

