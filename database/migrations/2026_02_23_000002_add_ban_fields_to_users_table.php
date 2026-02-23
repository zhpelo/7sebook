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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false)->after('role');
            $table->string('ban_reason', 500)->nullable()->after('is_banned');
            $table->dateTime('banned_at')->nullable()->after('ban_reason');

            $table->index('is_banned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_banned']);
            $table->dropColumn(['is_banned', 'ban_reason', 'banned_at']);
        });
    }
};
