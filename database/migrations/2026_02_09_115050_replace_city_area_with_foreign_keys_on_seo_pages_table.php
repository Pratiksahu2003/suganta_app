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
        if (Schema::hasColumn('seo_pages', 'city')) {
            Schema::table('seo_pages', function (Blueprint $table) {
                $table->dropIndex(['city']);
                $table->dropIndex(['area']);
                $table->dropColumn(['city', 'area']);
            });
        }

        if (!Schema::hasColumn('seo_pages', 'city_id')) {
            Schema::table('seo_pages', function (Blueprint $table) {
                $table->unsignedInteger('city_id')->nullable()->after('page_type');
                $table->unsignedInteger('area_id')->nullable()->after('city_id');
                $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
                $table->foreign('area_id')->references('id')->on('areas')->nullOnDelete();
                $table->index('city_id');
                $table->index('area_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropForeign(['area_id']);
            $table->dropIndex(['city_id']);
            $table->dropIndex(['area_id']);
            $table->dropColumn(['city_id', 'area_id']);
        });

        Schema::table('seo_pages', function (Blueprint $table) {
            $table->string('city')->nullable()->after('page_type');
            $table->string('area')->nullable()->after('city');
            $table->index('city');
            $table->index('area');
        });
    }
};
