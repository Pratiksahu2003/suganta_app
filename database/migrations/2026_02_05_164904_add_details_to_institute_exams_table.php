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
            $table->string('course_name')->nullable();
            $table->integer('course_duration_months')->nullable();
            $table->integer('students_cleared')->nullable();
            $table->decimal('fee_range_min', 10, 2)->nullable();
            $table->decimal('fee_range_max', 10, 2)->nullable();
            $table->text('course_description')->nullable();
            $table->json('course_features')->nullable();
            $table->json('study_materials')->nullable();
            $table->json('faculty_details')->nullable();
            $table->string('teaching_mode')->nullable(); // online, offline, hybrid
            $table->json('schedule_details')->nullable();
            $table->json('facilities')->nullable(); // Additional facilities specific to this exam course
            $table->text('admission_requirements')->nullable();
            $table->date('course_start_date')->nullable();
            $table->date('course_end_date')->nullable();
            $table->date('admission_deadline')->nullable();
            $table->boolean('scholarship_available')->default(false);
            $table->text('scholarship_details')->nullable();
            $table->boolean('placement_assistance')->default(false);
            $table->json('achievements')->nullable();
            $table->string('status')->default('active'); // active, inactive, upcoming
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institute_exams', function (Blueprint $table) {
            $table->dropColumn([
                'course_name',
                'course_duration_months',
                'students_cleared',
                'fee_range_min',
                'fee_range_max',
                'course_description',
                'course_features',
                'study_materials',
                'faculty_details',
                'teaching_mode',
                'schedule_details',
                'facilities',
                'admission_requirements',
                'course_start_date',
                'course_end_date',
                'admission_deadline',
                'scholarship_available',
                'scholarship_details',
                'placement_assistance',
                'achievements',
                'status'
            ]);
        });
    }
};
