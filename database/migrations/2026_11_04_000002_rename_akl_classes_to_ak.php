<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->whereIn('kelas', ['AKL 1', 'AKL 2', 'AKL 3', 'AKL 4'])
            ->update(['kelas' => DB::raw("REPLACE(kelas, 'AKL', 'AK')")]);

        DB::table('pending_members')
            ->whereIn('kelas', ['AKL 1', 'AKL 2', 'AKL 3', 'AKL 4'])
            ->update(['kelas' => DB::raw("REPLACE(kelas, 'AKL', 'AK')")]);
    }

    public function down(): void
    {
        DB::table('customers')
            ->whereIn('kelas', ['AK 1', 'AK 2', 'AK 3', 'AK 4'])
            ->update(['kelas' => DB::raw("REPLACE(kelas, 'AK', 'AKL')")]);

        DB::table('pending_members')
            ->whereIn('kelas', ['AK 1', 'AK 2', 'AK 3', 'AK 4'])
            ->update(['kelas' => DB::raw("REPLACE(kelas, 'AK', 'AKL')")]);
    }
};
