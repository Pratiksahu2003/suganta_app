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
        // Create profile_institute_info table if it doesn't exist
        if (!Schema::hasTable('profile_institute_info')) {
            Schema::create('profile_institute_info', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained()->onDelete('cascade');
                $table->string('institute_name')->nullable();
                $table->string('institute_type')->nullable(); // school, college, university, coaching, ngo
                $table->string('institute_category')->nullable(); // government, private, aided
                $table->string('affiliation_number')->nullable();
                $table->string('registration_number')->nullable();
                $table->string('udise_code')->nullable();
                $table->string('aicte_code')->nullable();
                $table->string('ugc_code')->nullable();
                $table->year('establishment_year')->nullable();
                $table->string('principal_name')->nullable();
                $table->string('principal_phone')->nullable();
                $table->string('principal_email')->nullable();
                $table->integer('total_students')->default(0);
                $table->integer('total_teachers')->default(0);
                $table->integer('total_branches')->default(1);
                $table->json('facilities')->nullable();
                $table->json('accreditations')->nullable();
                $table->json('affiliations')->nullable();
                $table->text('institute_description')->nullable();
                $table->json('courses_offered')->nullable();
                $table->json('specializations')->nullable();
                $table->boolean('is_main_branch')->default(true);
                $table->foreignId('parent_institute_id')->nullable()->constrained('profile_institute_info')->onDelete('set null');
                $table->timestamps();
                
                $table->index(['profile_id', 'institute_type']);
            });
        }

        // Create profile_student_info table if it doesn't exist
        if (!Schema::hasTable('profile_student_info')) {
            Schema::create('profile_student_info', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained()->onDelete('cascade');
                $table->string('student_id')->nullable();
                $table->string('current_class')->nullable();
                $table->string('current_school')->nullable();
                $table->string('board')->nullable();
                $table->string('stream')->nullable();
                $table->json('subjects_of_interest')->nullable();
                $table->json('learning_goals')->nullable();
                $table->string('learning_mode')->default('both');
                $table->decimal('budget_min', 8, 2)->nullable();
                $table->decimal('budget_max', 8, 2)->nullable();
                $table->string('preferred_timing')->nullable();
                $table->text('learning_challenges')->nullable();
                $table->text('special_requirements')->nullable();
                $table->json('extracurricular_interests')->nullable();
                $table->string('parent_name')->nullable();
                $table->string('parent_phone')->nullable();
                $table->string('parent_email')->nullable();
                $table->string('guardian_name')->nullable();
                $table->string('guardian_phone')->nullable();
                $table->string('guardian_email')->nullable();
                $table->json('current_grades')->nullable();
                $table->json('target_grades')->nullable();
                $table->boolean('previous_tutoring_experience')->default(false);
                $table->timestamps();
                
                $table->index(['profile_id', 'current_class']);
                $table->index('learning_mode');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_student_info');
        Schema::dropIfExists('profile_institute_info');
    }
}; 