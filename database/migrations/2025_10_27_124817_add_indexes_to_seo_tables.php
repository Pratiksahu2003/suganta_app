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
        // Add composite index for LIKE queries on slug in area_seo_contents
        Schema::table('area_seo_contents', function (Blueprint $table) {
            // Add index on slug for faster LIKE queries
            $table->index('slug', 'idx_area_seo_slug');
            // Add index on is_active for faster filtering
            $table->index('is_active', 'idx_area_seo_active');
            // Composite index for common query pattern
            $table->index(['slug', 'is_active'], 'idx_area_seo_slug_active');
        });

        // Add indexes for city_seo_contents
        Schema::table('city_seo_contents', function (Blueprint $table) {
            // Add index on slug for faster LIKE queries
            $table->index('slug', 'idx_city_seo_slug');
            // Add index on is_active for faster filtering
            $table->index('is_active', 'idx_city_seo_active');
            // Composite index for common query pattern
            $table->index(['slug', 'is_active'], 'idx_city_seo_slug_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('area_seo_contents', function (Blueprint $table) {
            $table->dropIndex('idx_area_seo_slug');
            $table->dropIndex('idx_area_seo_active');
            $table->dropIndex('idx_area_seo_slug_active');
        });

        Schema::table('city_seo_contents', function (Blueprint $table) {
            $table->dropIndex('idx_city_seo_slug');
            $table->dropIndex('idx_city_seo_active');
            $table->dropIndex('idx_city_seo_slug_active');
        });
    }
};
