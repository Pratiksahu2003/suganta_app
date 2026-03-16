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
        Schema::connection('ai_mysql')->create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('subject')->nullable();
            $table->string('status')->default('active');
            $table->string('model')->default('gemini-2.5-flash-lite');
            $table->string('purpose')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('total_prompt_tokens')->default(0);
            $table->unsignedBigInteger('total_completion_tokens')->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_error_code')->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('model');
            $table->index('purpose');
            $table->index('status');
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('ai_mysql')->dropIfExists('ai_conversations');
    }
};

