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
        Schema::table('customers', function (Blueprint $table) {
            // Grade: 10, 11, or 12
            $table->tinyInteger('grade')->nullable()->after('balance')->comment('Tingkat kelas: 10, 11, atau 12');
            // Class values are maintained by Customer::availableKelas().
            $table->string('kelas', 20)->nullable()->after('grade')->comment('Jurusan dan kelas anggota.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['grade', 'kelas']);
        });
    }
};
