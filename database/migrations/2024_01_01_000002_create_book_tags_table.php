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
        Schema::create('book_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade')->onUpdate('cascade');
            $table->string('tag_name', 100)->comment('标签名称');
            $table->timestamp('created_at')->useCurrent()->comment('创建时间');

            $table->index('book_id');
            $table->index('tag_name');
            $table->index('created_at');
            $table->index(['book_id', 'tag_name']);
            $table->index(['tag_name', 'book_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_tags');
    }
};
