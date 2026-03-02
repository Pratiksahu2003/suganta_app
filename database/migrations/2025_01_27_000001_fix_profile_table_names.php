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
        // Ensure profile_institute_info table exists
        if (!Schema::hasTable('profile_institute_info')) {
            Schema::create('profile_institute_info', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained()->onDelete('cascade');
                $table->string('institute_name')->nullable();
                $table->string('institute_type')->nullable();
                $table->string('institute_category')->nullable();
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

        // Ensure profile_student_info table exists
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

        // Ensure profile_teaching_info table exists
        if (!Schema::hasTable('profile_teaching_info')) {
            Schema::create('profile_teaching_info', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained()->onDelete('cascade');
                $table->integer('teaching_experience_years')->default(0);
                $table->integer('total_students_taught')->default(0);
                $table->decimal('hourly_rate', 8, 2)->nullable();
                $table->decimal('monthly_rate', 8, 2)->nullable();
                $table->string('teaching_mode')->default('both');
                $table->boolean('online_classes')->default(false);
                $table->boolean('home_tuition')->default(false);
                $table->boolean('institute_classes')->default(false);
                $table->integer('travel_radius_km')->default(10);
                $table->json('subjects_taught')->nullable();
                $table->json('grade_levels_taught')->nullable();
                $table->json('exam_preparation')->nullable();
                $table->text('teaching_philosophy')->nullable();
                $table->json('teaching_methods')->nullable();
                $table->json('available_timings')->nullable();
                $table->string('availability_status')->default('available');
                $table->timestamps();
                
                $table->index(['profile_id', 'teaching_mode']);
                $table->index('availability_status');
            });
        }

        // Ensure profile_professional_info table exists
        if (!Schema::hasTable('profile_professional_info')) {
            Schema::create('profile_professional_info', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained()->onDelete('cascade');
                $table->string('profession')->nullable();
                $table->string('company_name')->nullable();
                $table->string('job_title')->nullable();
                $table->string('department')->nullable();
                $table->integer('work_experience')->default(0);
                $table->json('skills')->nullable();
                $table->json('certifications')->nullable();
                $table->json('awards')->nullable();
                $table->json('publications')->nullable();
                $table->json('research_interests')->nullable();
                $table->timestamps();
                
                $table->index(['profile_id', 'profession']);
            });
        }

        // Ensure profile_social_links table exists
        if (!Schema::hasTable('profile_social_links')) {
            Schema::create('profile_social_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained()->onDelete('cascade');
                $table->string('platform');
                $table->string('url');
                $table->string('username')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index(['profile_id', 'platform']);
                $table->unique(['profile_id', 'platform']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop tables in down() to avoid data loss
        // Tables will be dropped by their respective migrations
    }
}; 