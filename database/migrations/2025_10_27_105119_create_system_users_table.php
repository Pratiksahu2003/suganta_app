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
        Schema::create('system_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // If user is logged in
            $table->string('unique_system_id')->unique()->index(); // Unique identifier for each system/device
            $table->string('device_name')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet, laptop
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('platform')->nullable(); // Windows, macOS, Linux, Android, iOS
            $table->string('platform_version')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Location Data
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('location_string')->nullable(); // Full address string
            $table->string('timezone')->nullable();
            
            // Session data
            $table->boolean('location_permission_granted')->default(false);
            $table->boolean('location_permission_denied')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['unique_system_id']);
            $table->index(['last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_users');
    }
};
