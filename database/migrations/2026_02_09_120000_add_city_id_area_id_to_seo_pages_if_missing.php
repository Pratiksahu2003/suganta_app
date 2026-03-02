<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds city_id and area_id to seo_pages without foreign keys so it runs
     * even when cities/areas tables are missing or not yet migrated.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('seo_pages', 'city_id')) {
            Schema::table('seo_pages', function (Blueprint $table) {
                $table->unsignedBigInteger('city_id')->nullable()->after('page_type');
                $table->index('city_id');
            });
        }
        if (!Schema::hasColumn('seo_pages', 'area_id')) {
            Schema::table('seo_pages', function (Blueprint $table) {
                $table->unsignedBigInteger('area_id')->nullable()->after('city_id');
                $table->index('area_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('seo_pages', 'area_id')) {
            Schema::table('seo_pages', function (Blueprint $table) {
                $table->dropIndex(['area_id']);
                $table->dropColumn('area_id');
            });
        }
        if (Schema::hasColumn('seo_pages', 'city_id')) {
            Schema::table('seo_pages', function (Blueprint $table) {
                $table->dropIndex(['city_id']);
                $table->dropColumn('city_id');
            });
        }
    }
};
