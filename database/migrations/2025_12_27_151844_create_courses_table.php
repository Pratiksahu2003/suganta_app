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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Created by (teacher or institute)
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->foreignId('subject_id')->nullable()->constrained()->onDelete('set null');
            $table->string('thumbnail')->nullable();
            $table->string('preview_video_id')->nullable(); // YouTube video ID for preview
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('beginner');
            $table->string('language')->default('english');
            $table->json('what_you_will_learn')->nullable();
            $table->json('requirements')->nullable();
            $table->json('target_audience')->nullable();
            $table->integer('total_duration_minutes')->default(0);
            $table->integer('total_videos')->default(0);
            $table->integer('total_students')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_free')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['subject_id', 'status']);
            $table->index('slug');
            $table->index(['status', 'is_featured']);
        });

        Schema::create('course_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('youtube_video_id')->unique(); // YouTube video ID
            $table->string('youtube_video_url')->nullable(); // Full YouTube URL
            $table->integer('duration_minutes')->default(0);
            $table->integer('order')->default(0);
            $table->boolean('is_preview')->default(false); // Free preview video
            $table->json('resources')->nullable(); // Additional resources (PDFs, etc.)
            $table->text('transcript')->nullable();
            $table->timestamps();
            
            $table->index(['course_id', 'order']);
            $table->index('youtube_video_id');
        });

        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['course_id', 'order']);
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Student
            $table->enum('status', ['enrolled', 'completed', 'dropped'])->default('enrolled');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['course_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('course_videos');
        Schema::dropIfExists('course_sections');
        Schema::dropIfExists('courses');
    }
};
