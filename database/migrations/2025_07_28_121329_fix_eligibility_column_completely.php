<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Drop the existing eligibility column
            $table->dropColumn('eligibility');
        });

        Schema::table('exams', function (Blueprint $table) {
            // Recreate eligibility column as text
            $table->text('eligibility')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Drop the text column
            $table->dropColumn('eligibility');
        });

        Schema::table('exams', function (Blueprint $table) {
            // Recreate as JSON if rolling back
            $table->json('eligibility')->nullable();
        });
    }
}; 