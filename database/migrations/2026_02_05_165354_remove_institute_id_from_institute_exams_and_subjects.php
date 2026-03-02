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
        Schema::table('institute_exams', function (Blueprint $table) {
            // Check if foreign key exists before dropping (good practice, but in Laravel we often assume naming convention)
            // Assuming standard naming convention: institute_exams_institute_id_foreign
            $table->dropForeign(['institute_id']);
            $table->dropColumn('institute_id');
        });

        Schema::table('institute_subjects', function (Blueprint $table) {
            $table->dropForeign(['institute_id']);
            $table->dropColumn('institute_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institute_exams', function (Blueprint $table) {
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
        });

        Schema::table('institute_subjects', function (Blueprint $table) {
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
        });
    }
};
