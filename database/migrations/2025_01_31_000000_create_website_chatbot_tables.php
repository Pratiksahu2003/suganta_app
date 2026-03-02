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
        Schema::create('website_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('page_url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('website_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_chat_session_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_type', ['user', 'bot']);
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['website_chat_session_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_chat_messages');
        Schema::dropIfExists('website_chat_sessions');
    }
};
