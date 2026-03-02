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
        // Remove old string columns from profiles table
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'gender',           // Replaced by gender_id
                'country',          // Replaced by country_id
                'timezone',         // Replaced by timezone_id
            ]);
        });

        // Remove old string columns from profile_teaching_info table
        Schema::table('profile_teaching_info', function (Blueprint $table) {
            $table->dropColumn([
                'teaching_mode',        // Replaced by teaching_mode_id
                'availability_status',  // Replaced by availability_status_id
            ]);
        });

        // Remove old string columns from profile_institute_info table
        Schema::table('profile_institute_info', function (Blueprint $table) {
            $table->dropColumn([
                'institute_type',       // Replaced by institute_type_id
                'institute_category',   // Replaced by institute_category_id
                'establishment_year',   // Replaced by establishment_year_id
                'total_students',       // Replaced by total_students_id
                'total_teachers',       // Replaced by total_teachers_id
            ]);
        });

        // Remove old string columns from profile_student_info table
        Schema::table('profile_student_info', function (Blueprint $table) {
            $table->dropColumn([
                'current_class',        // Replaced by current_class_id
                'board',               // Replaced by board_id
                'stream',              // Replaced by stream_id
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add old string columns to profiles table
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('gender_id');
            $table->string('country')->nullable()->after('country_id');
            $table->string('timezone')->nullable()->after('timezone_id');
        });

        // Re-add old string columns to profile_teaching_info table
        Schema::table('profile_teaching_info', function (Blueprint $table) {
            $table->string('teaching_mode')->nullable()->after('teaching_mode_id');
            $table->string('availability_status')->nullable()->after('availability_status_id');
        });

        // Re-add old string columns to profile_institute_info table
        Schema::table('profile_institute_info', function (Blueprint $table) {
            $table->string('institute_type')->nullable()->after('institute_type_id');
            $table->string('institute_category')->nullable()->after('institute_category_id');
            $table->string('establishment_year')->nullable()->after('establishment_year_id');
            $table->string('total_students')->nullable()->after('total_students_id');
            $table->string('total_teachers')->nullable()->after('total_teachers_id');
        });

        // Re-add old string columns to profile_student_info table
        Schema::table('profile_student_info', function (Blueprint $table) {
            $table->string('current_class')->nullable()->after('current_class_id');
            $table->string('board')->nullable()->after('board_id');
            $table->string('stream')->nullable()->after('stream_id');
        });
    }
}; 