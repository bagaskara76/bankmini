<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pin_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('teller_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            // Status: waiting (menunggu nasabah), verified (PIN benar), expired (kadaluarsa)
            $table->enum('status', ['waiting', 'verified', 'expired'])->default('waiting');
            $table->decimal('amount', 15, 0); // nominal penarikan yang diajukan teller
            $table->string('note', 500)->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pin_sessions');
    }
};

