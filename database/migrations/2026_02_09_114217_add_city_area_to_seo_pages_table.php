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
            $table->string('city')->nullable()->after('page_type');
            $table->string('area')->nullable()->after('city');
            
            // Add indexes for better performance
            $table->index('city');
            $table->index('area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropIndex(['area']);
            $table->dropColumn(['city', 'area']);
        });
    }
};
