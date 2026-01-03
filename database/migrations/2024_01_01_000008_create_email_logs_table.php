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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_email', 255)->comment('收件人邮箱');
            $table->string('subject', 255)->comment('邮件主题');
            $table->text('content')->nullable()->comment('邮件内容');
            $table->string('type', 50)->default('password_reset')->comment('邮件类型：password_reset等');
            $table->string('status', 20)->default('pending')->comment('发送状态：pending, sent, failed');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->timestamp('sent_at')->nullable()->comment('发送时间');
            $table->timestamps();

            $table->index('to_email');
            $table->index('type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
