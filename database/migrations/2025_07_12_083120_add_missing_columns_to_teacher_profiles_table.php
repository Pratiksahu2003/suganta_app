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
        Schema::table('teacher_profiles', function (Blueprint $table) {
            // Add institute experience column if it doesn't exist
            if (!Schema::hasColumn('teacher_profiles', 'institute_experience')) {
                $table->text('institute_experience')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_profiles', 'institute_experience')) {
                $table->dropColumn('institute_experience');
            }
        });
    }
};
