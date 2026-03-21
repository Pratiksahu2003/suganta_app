<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->create('chat_conversations', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['private', 'group'])->default('private');
            $table->string('title')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'last_message_at']);
        });

        Schema::connection('ai_mysql')->create('chat_conversation_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'left_at']);
        });

        Schema::connection('ai_mysql')->create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->text('message');
            $table->unsignedBigInteger('reply_to')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
            $table->index('sender_id');
        });

        Schema::connection('ai_mysql')->create('chat_message_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at');

            $table->unique(['message_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });

        Schema::connection('ai_mysql')->create('chat_message_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('reaction', 32);
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
            $table->index(['reaction']);
        });
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->dropIfExists('chat_message_reactions');
        Schema::connection('ai_mysql')->dropIfExists('chat_message_reads');
        Schema::connection('ai_mysql')->dropIfExists('chat_messages');
        Schema::connection('ai_mysql')->dropIfExists('chat_conversation_participants');
        Schema::connection('ai_mysql')->dropIfExists('chat_conversations');
    }
};
