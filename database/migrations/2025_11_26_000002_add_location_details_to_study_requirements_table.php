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
        Schema::table('study_requirements', function (Blueprint $table) {
            $table->string('location_area')->nullable()->after('location_city');
            $table->string('location_pincode', 12)->nullable()->after('location_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_requirements', function (Blueprint $table) {
            $table->dropColumn(['location_area', 'location_pincode']);
        });
    }
};

