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
        Schema::create('branch_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('institutes')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['meeting', 'training', 'exam', 'session', 'holiday', 'other'])->default('other');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('location')->nullable();
            $table->integer('max_participants')->nullable();
            $table->json('participants')->nullable(); // Array of user IDs
            $table->json('settings')->nullable(); // Additional event settings
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, monthly
            $table->date('recurrence_end_date')->nullable();
            $table->timestamps();
            
            $table->index(['branch_id', 'start_time']);
            $table->index(['created_by', 'start_time']);
            $table->index(['type', 'status']);
            $table->index('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_events');
    }
}; 