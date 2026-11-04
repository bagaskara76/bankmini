<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->whereIn('kelas', ['LPS 1', 'LPS 2'])
            ->update(['kelas' => 'LPS']);

        DB::table('pending_members')
            ->whereIn('kelas', ['LPS 1', 'LPS 2'])
            ->update(['kelas' => 'LPS']);
    }

    public function down(): void
    {
        // The original section number cannot be recovered after merging.
    }
};
