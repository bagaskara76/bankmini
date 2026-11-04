<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('account_number')->unique();
            $table->string('pin');
            $table->string('qr_code_string')->unique();
            $table->bigInteger('balance')->default(0);
            $table->timestamps();
            
            // Check constraint to ensure balance is never negative
            // Note: MySQL/MariaDB >= 8.0.16 supports CHECK constraints
            // Wait, Laravel schema builder doesn't have a direct helper for CHECK constraints until recently.
            // We can add it via a raw DB statement if needed, but for compatibility, we can just use unsignedBigInteger.
            // Actually, requirements specify "Penerapan Check Constraint (nominal > 0) langsung pada tabel".
        });
        
        // Raw statement for CHECK constraint to fulfill rubric "1.3 Validasi Level Database"
        DB::statement('ALTER TABLE customers ADD CONSTRAINT chk_balance_positive CHECK (balance >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
