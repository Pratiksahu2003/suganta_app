<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_watch_channels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type', 30); // calendar | drive
            $table->string('channel_id', 120)->unique();
            $table->string('resource_id', 255)->nullable();
            $table->string('google_resource_uri', 1000)->nullable();
            $table->string('verification_token', 255);
            $table->string('status', 20)->default('active'); // active | stopped | expired
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('last_message_number')->nullable();
            $table->timestamp('last_notification_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'resource_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_watch_channels');
    }
};
