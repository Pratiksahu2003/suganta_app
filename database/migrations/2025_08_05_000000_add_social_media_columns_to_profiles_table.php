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
        Schema::table('profiles', function (Blueprint $table) {
            // Add social media columns that ProfileController expects
            $table->string('facebook_url')->nullable()->after('website');
            $table->string('twitter_url')->nullable()->after('facebook_url');
            $table->string('instagram_url')->nullable()->after('twitter_url');
            $table->string('linkedin_url')->nullable()->after('instagram_url');
            $table->string('youtube_url')->nullable()->after('linkedin_url');
            $table->string('tiktok_url')->nullable()->after('youtube_url');
            $table->string('telegram_username')->nullable()->after('tiktok_url');
            $table->string('discord_username')->nullable()->after('telegram_username');
            $table->string('github_url')->nullable()->after('discord_username');
            $table->string('portfolio_url')->nullable()->after('github_url');
            $table->string('blog_url')->nullable()->after('portfolio_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_url',
                'twitter_url',
                'instagram_url',
                'linkedin_url',
                'youtube_url',
                'tiktok_url',
                'telegram_username',
                'discord_username',
                'github_url',
                'portfolio_url',
                'blog_url'
            ]);
        });
    }
}; 