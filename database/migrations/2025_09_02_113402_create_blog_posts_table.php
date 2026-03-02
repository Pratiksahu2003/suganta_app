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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('post_type')->default('post');
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('video_url')->nullable();
            $table->json('gallery_images')->nullable();
            $table->enum('status', ['draft', 'published', 'private', 'scheduled'])->default('draft');
            $table->string('category')->nullable();
            $table->json('categories')->nullable();
            $table->json('tags')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('sticky')->default(false);
            $table->boolean('allow_comments')->default(true);
            $table->boolean('allow_ratings')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('difficulty_level')->nullable();
            $table->string('target_audience')->nullable();
            $table->string('education_level')->nullable();
            $table->integer('reading_time')->nullable();
            $table->integer('word_count')->nullable();
            $table->json('seo_data')->nullable();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'category']);
            $table->index(['is_featured', 'published_at']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
