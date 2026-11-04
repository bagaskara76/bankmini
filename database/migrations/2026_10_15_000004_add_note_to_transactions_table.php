<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('transactions', 'note')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('note', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'note')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('note');
            });
        }
    }
};