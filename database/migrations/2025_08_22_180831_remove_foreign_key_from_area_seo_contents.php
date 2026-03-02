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
        Schema::table('area_seo_contents', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['tutoring_area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('area_seo_contents', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('tutoring_area_id')->references('id')->on('tutoring_areas')->onDelete('cascade');
        });
    }
};
