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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('teller_id')->constrained('users')->onDelete('restrict');
            $table->enum('type', ['deposit', 'withdrawal']);
            $table->bigInteger('amount');
            $table->enum('status', ['completed', 'pending_approval', 'rejected'])->default('completed');
            $table->timestamps();
        });

        // Add check constraint for amount > 0 as per UKK requirement
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_transaction_amount_positive CHECK (amount > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
