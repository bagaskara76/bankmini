<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubah kolom amount di tabel transactions dari bigint menjadi decimal(15,0).
     * decimal(15,0) cocok untuk nominal uang Rupiah (tidak ada pecahan desimal).
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 15, 0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
        });
    }
};

