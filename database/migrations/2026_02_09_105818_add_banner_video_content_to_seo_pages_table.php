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
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->string('banner_image')->nullable()->after('og_image');
            $table->string('youtube_video_url')->nullable()->after('banner_image');
            $table->longText('seo_content')->nullable()->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->dropColumn(['banner_image', 'youtube_video_url', 'seo_content']);
        });
    }
};
