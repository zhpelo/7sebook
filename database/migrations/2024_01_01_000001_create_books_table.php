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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_url', 500)->nullable();
            $table->date('publish_date')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('isbn', 20)->nullable();
            $table->string('publisher', 100)->nullable();
            $table->dateTime('published_at')->nullable()->comment('出版日期');
            $table->integer('page_count')->nullable();
            $table->string('languages', 50)->default('中文')->comment('语言');
            $table->text('identifiers')->nullable()->comment('唯一标识符，如 ASIN、ISBN');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('file_name')->comment('文件名');
            $table->string('file_path')->comment('文件路径');
            $table->string('file_size', 10)->comment('文件大小');
            $table->string('file_format', 10)->comment('文件格式');
            $table->string('file_md5', 32)->comment('文件MD5');
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->timestamps();

            $table->index('title');
            $table->index('author');
            $table->index('category');
            $table->index('publish_date');
            $table->index('isbn');
            $table->index('publisher');
            $table->fullText(['title']);
            $table->fullText(['author']);
            $table->fullText(['description']);
            $table->fullText(['title', 'author', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
