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
        Schema::create('teacher_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date');
            $table->time('time');
            $table->integer('duration'); // in minutes
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->integer('max_students')->default(1);
            $table->decimal('price', 8, 2);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->json('settings')->nullable(); // Additional session settings
            $table->timestamps();
            
            $table->index(['teacher_profile_id', 'status']);
            $table->index(['date', 'time']);
            $table->index('subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_sessions');
    }
};
