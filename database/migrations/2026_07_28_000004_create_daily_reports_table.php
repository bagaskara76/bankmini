<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teller_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->date('report_date');
            $table->bigInteger('total_deposit')->default(0);
            $table->bigInteger('total_withdrawal')->default(0);
            $table->bigInteger('system_cash')->default(0);
            $table->bigInteger('physical_cash')->default(0);
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE daily_reports ADD CONSTRAINT chk_system_cash_positive CHECK (system_cash >= 0)');
        DB::statement('ALTER TABLE daily_reports ADD CONSTRAINT chk_physical_cash_positive CHECK (physical_cash >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
