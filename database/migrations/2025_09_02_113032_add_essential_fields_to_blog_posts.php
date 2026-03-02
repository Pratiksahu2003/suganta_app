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
        Schema::table('blog_posts', function (Blueprint $table) {
            // Add only the most essential missing fields
            if (!Schema::hasColumn('blog_posts', 'post_type')) {
                $table->string('post_type')->default('post')->after('slug');
            }
            if (!Schema::hasColumn('blog_posts', 'meta_keywords')) {
                $table->string('meta_keywords')->nullable()->after('meta_description');
            }
            if (!Schema::hasColumn('blog_posts', 'sticky')) {
                $table->boolean('sticky')->default(false)->after('is_featured');
            }
            if (!Schema::hasColumn('blog_posts', 'allow_ratings')) {
                $table->boolean('allow_ratings')->default(false)->after('allow_comments');
            }
            if (!Schema::hasColumn('blog_posts', 'is_premium')) {
                $table->boolean('is_premium')->default(false)->after('allow_ratings');
            }
            if (!Schema::hasColumn('blog_posts', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('is_premium');
            }
            if (!Schema::hasColumn('blog_posts', 'video_url')) {
                $table->string('video_url')->nullable()->after('featured_image');
            }
            if (!Schema::hasColumn('blog_posts', 'categories')) {
                $table->json('categories')->nullable()->after('category');
            }
            if (!Schema::hasColumn('blog_posts', 'difficulty_level')) {
                $table->string('difficulty_level')->nullable()->after('categories');
            }
            if (!Schema::hasColumn('blog_posts', 'target_audience')) {
                $table->string('target_audience')->nullable()->after('difficulty_level');
            }
            if (!Schema::hasColumn('blog_posts', 'education_level')) {
                $table->string('education_level')->nullable()->after('target_audience');
            }
            if (!Schema::hasColumn('blog_posts', 'reading_time')) {
                $table->integer('reading_time')->nullable()->after('education_level');
            }
            if (!Schema::hasColumn('blog_posts', 'word_count')) {
                $table->integer('word_count')->nullable()->after('reading_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn([
                'post_type',
                'meta_keywords',
                'sticky',
                'allow_ratings',
                'is_premium',
                'price',
                'video_url',
                'categories',
                'difficulty_level',
                'target_audience',
                'education_level',
                'reading_time',
                'word_count'
            ]);
        });
    }
};
