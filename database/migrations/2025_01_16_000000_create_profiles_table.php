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
        // Main profiles table with essential fields
        if (!Schema::hasTable('profiles')) {
            Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Basic Profile Information
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('display_name')->nullable();
            $table->text('bio')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('nationality')->nullable();
            
            // Contact Information
            $table->string('phone_primary')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('website')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            
            // Location Information
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('country')->default('India');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('timezone')->default('Asia/Kolkata');
            $table->boolean('location_auto_detected')->default(false);
            $table->timestamp('location_last_updated')->nullable();
            
            // Educational Information
            $table->string('highest_qualification')->nullable();
            $table->string('institution_name')->nullable();
            $table->string('field_of_study')->nullable();
            $table->year('graduation_year')->nullable();
            $table->decimal('cgpa', 3, 2)->nullable();
            $table->json('languages_spoken')->nullable();
            
            // Media & Files
            $table->string('profile_image')->nullable();
            $table->string('cover_image')->nullable();
            
            // Verification & Status
            $table->boolean('is_verified')->default(false);
            $table->string('verification_status')->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('profile_completion_status', ['basic', 'detailed', 'complete'])->default('basic');
            $table->integer('profile_completion_percentage')->default(0);
            
            // Preferences & Settings
            $table->json('preferences')->nullable();
            $table->string('language')->default('en');
            $table->string('date_format')->default('Y-m-d');
            $table->enum('time_format', ['12', '24'])->default('12');
            
            // Analytics & Tracking
            $table->integer('profile_views')->default(0);
            $table->integer('profile_likes')->default(0);
            $table->integer('profile_shares')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            
            // SEO & Marketing
            $table->string('slug')->nullable()->unique();
            $table->text('meta_description')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['city', 'state', 'is_verified']);
            $table->index(['latitude', 'longitude']);
            $table->index(['verification_status', 'is_active']);
            $table->index(['profile_completion_status', 'is_active']);
            $table->index('slug');
        });

        // Social media links table
        Schema::create('profile_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            $table->string('platform'); // facebook, twitter, instagram, etc.
            $table->string('url')->nullable();
            $table->string('username')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['profile_id', 'platform']);
            $table->index(['platform', 'is_active']);
        });

        // Teaching information table (for teachers)
        Schema::create('profile_teaching_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            $table->integer('teaching_experience_years')->default(0);
            $table->integer('total_students_taught')->default(0);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();
            $table->enum('teaching_mode', ['online', 'offline', 'both'])->default('both');
            $table->boolean('online_classes')->default(true);
            $table->boolean('home_tuition')->default(true);
            $table->boolean('institute_classes')->default(false);
            $table->integer('travel_radius_km')->nullable();
            $table->json('subjects_taught')->nullable();
            $table->json('grade_levels_taught')->nullable();
            $table->json('exam_preparation')->nullable();
            $table->text('teaching_philosophy')->nullable();
            $table->json('teaching_methods')->nullable();
            $table->json('available_timings')->nullable();
            $table->enum('availability_status', ['available', 'busy', 'unavailable'])->default('available');
            $table->timestamps();
            
            $table->index(['profile_id', 'availability_status']);
            $table->index('hourly_rate');
        });

        // Institute information table (for institutes)
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

        // Student information table (for students)
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

        // Professional information table
        Schema::create('profile_professional_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            $table->string('profession')->nullable();
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->text('work_experience')->nullable();
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();
            $table->json('awards')->nullable();
            $table->json('publications')->nullable();
            $table->json('research_interests')->nullable();
            $table->timestamps();
            
            $table->index(['profile_id', 'profession']);
        });

        // Verification documents table
        Schema::create('profile_verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            $table->string('document_type'); // id_proof, address_proof, qualification, etc.
            $table->string('document_number')->nullable();
            $table->string('document_file')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index(['profile_id', 'document_type']);
            $table->index(['status', 'document_type']);
        });

        // Profile media table
        Schema::create('profile_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            $table->string('type'); // gallery, document, certificate
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->integer('file_size');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['profile_id', 'type']);
            $table->index(['type', 'is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_media');
        Schema::dropIfExists('profile_verification_documents');
        Schema::dropIfExists('profile_professional_info');
        Schema::dropIfExists('profile_student_info');
        Schema::dropIfExists('profile_institute_info');
        Schema::dropIfExists('profile_teaching_info');
        Schema::dropIfExists('profile_social_links');
        Schema::dropIfExists('profiles');
    }
}; 