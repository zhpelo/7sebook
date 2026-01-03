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
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_address', 45)->nullable()->comment('IP地址');
            $table->string('search_query', 255);
            $table->enum('search_type', ['all', 'title', 'author', 'description'])->default('all');
            $table->integer('results_count')->default(0);
            $table->timestamp('search_time')->useCurrent();

            $table->index('user_id');
            $table->index('search_query');
            $table->index('search_time');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_history');
    }
};
