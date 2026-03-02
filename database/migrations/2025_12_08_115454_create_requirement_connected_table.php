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
        Schema::create('requirement_connected', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requirement_id')->constrained('study_requirements')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('connected_at')->useCurrent();
            $table->timestamps();
            
            // Ensure a user can only connect once per requirement
            $table->unique(['requirement_id', 'user_id']);
            $table->index(['requirement_id', 'status']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requirement_connected');
    }
};
