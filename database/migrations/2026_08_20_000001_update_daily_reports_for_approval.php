<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->bigInteger('initial_capital')->default(0)->after('report_date');
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])
                ->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn('initial_capital');
            $table->enum('status', ['draft', 'approved', 'disputed'])
                ->default('draft')->change();
        });
    }
};