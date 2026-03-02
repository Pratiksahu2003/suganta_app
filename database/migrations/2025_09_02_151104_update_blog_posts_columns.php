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
            // Rename columns to match model expectations
            $table->renameColumn('views_count', 'views');
            $table->renameColumn('is_featured', 'featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // Revert column names
            $table->renameColumn('views', 'views_count');
            $table->renameColumn('featured', 'is_featured');
        });
    }
};
