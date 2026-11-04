<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recovery_otps', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('consumed_at');
            $table->string('reset_token_hash', 64)->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('recovery_otps', function (Blueprint $table) {
            $table->dropColumn(['verified_at', 'reset_token_hash']);
        });
    }
};
