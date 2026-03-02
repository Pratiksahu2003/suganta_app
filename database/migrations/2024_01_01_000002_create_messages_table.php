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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->enum('type', ['text', 'image', 'file', 'audio', 'video'])->default('text');
            $table->enum('status', ['sending', 'sent', 'delivered', 'read'])->default('sent');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->json('metadata')->nullable(); // For file info, reactions, etc.
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
