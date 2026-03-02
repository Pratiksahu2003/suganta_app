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
        // Create favorites table
        if (!Schema::hasTable('favorites')) {
            Schema::create('favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->morphs('favoriteable'); // Polymorphic relationship for teacher, institute
                $table->timestamps();
                
                $table->unique(['user_id', 'favoriteable_type', 'favoriteable_id']);
                $table->index(['favoriteable_type', 'favoriteable_id']);
            });
        }

        // Create bookings table
        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->string('booking_id')->unique();
                $table->foreignId('student_id')->constrained('student_profiles')->onDelete('cascade');
                $table->foreignId('teacher_id')->constrained('teacher_profiles')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->datetime('scheduled_at');
                $table->integer('duration_minutes')->default(60);
                $table->decimal('rate_per_hour', 8, 2);
                $table->decimal('total_amount', 10, 2);
                $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('pending');
                $table->enum('session_type', ['online', 'offline', 'hybrid'])->default('online');
                $table->string('meeting_link')->nullable();
                $table->text('location')->nullable();
                $table->text('notes')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('payment_details')->nullable();
                $table->timestamps();
                
                $table->index(['student_id', 'status']);
                $table->index(['teacher_id', 'status']);
                $table->index(['scheduled_at', 'status']);
            });
        }

        // Create sessions table
        if (!Schema::hasTable('teacher_sessions')) {
            Schema::create('teacher_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teacher_profiles')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->text('description');
                $table->decimal('rate_per_hour', 8, 2);
                $table->integer('max_students')->default(1);
                $table->enum('session_type', ['online', 'offline', 'hybrid'])->default('online');
                $table->json('available_timings')->nullable();
                $table->string('grade_level')->nullable();
                $table->text('requirements')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index(['teacher_id', 'is_active']);
                $table->index(['subject_id', 'is_active']);
            });
        }

        // Create student_teachers relationship table
        if (!Schema::hasTable('student_teachers')) {
            Schema::create('student_teachers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('student_profiles')->onDelete('cascade');
                $table->foreignId('teacher_id')->constrained('teacher_profiles')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained()->onDelete('cascade');
                $table->enum('status', ['active', 'inactive', 'completed'])->default('active');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->json('notes')->nullable();
                $table->timestamps();
                
                $table->unique(['student_id', 'teacher_id', 'subject_id']);
                $table->index(['student_id', 'status']);
                $table->index(['teacher_id', 'status']);
            });
        }

        // Create notifications table
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                
                $table->index(['notifiable_type', 'notifiable_id']);
                $table->index('read_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('student_teachers');
        Schema::dropIfExists('teacher_sessions');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('favorites');
    }
};
