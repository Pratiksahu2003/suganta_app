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
        // Check if the table exists and has the correct structure
        if (Schema::hasTable('area_seo_contents')) {
            // Check if tutoring_area_id column exists
            if (!Schema::hasColumn('area_seo_contents', 'tutoring_area_id')) {
                // Add the missing column without foreign key constraint for now
                Schema::table('area_seo_contents', function (Blueprint $table) {
                    $table->unsignedBigInteger('tutoring_area_id')->nullable()->after('id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the column if it was added
        if (Schema::hasTable('area_seo_contents') && Schema::hasColumn('area_seo_contents', 'tutoring_area_id')) {
            Schema::table('area_seo_contents', function (Blueprint $table) {
                $table->dropColumn('tutoring_area_id');
            });
        }
    }
};
