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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('conversation_id')->unique();
            $table->foreignId('initiator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('users')->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->enum('type', ['general', 'support', 'group'])->default('general');
            $table->enum('status', ['active', 'archived', 'blocked'])->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Ensure unique conversation between two users
            $table->unique(['initiator_id', 'participant_id']);
            $table->index(['initiator_id', 'status']);
            $table->index(['participant_id', 'status']);
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
