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
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Student who wrote the review
                $table->morphs('reviewable'); // Polymorphic relationship for teacher, institute, branch
                $table->integer('rating')->default(5); // 1-5 stars
                $table->text('comment')->nullable();
                $table->string('title')->nullable(); // Review title
                $table->json('tags')->nullable(); // Tags like "helpful", "knowledgeable", etc.
                $table->boolean('is_verified')->default(false); // Admin verified review
                $table->boolean('is_helpful')->default(false); // Marked as helpful by other users
                $table->integer('helpful_count')->default(0); // Number of users who found it helpful
                $table->string('status')->default('published'); // published, pending, rejected, hidden
                $table->timestamp('reviewed_at')->nullable(); // When the review was written
                $table->timestamps();
                
                $table->index(['user_id', 'reviewable_type']);
                $table->index(['rating', 'status']);
                $table->index('is_verified');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
