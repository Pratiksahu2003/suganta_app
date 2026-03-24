<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Complete Education Platform Database Setup
     * This migration creates the entire database structure from scratch
     */
    public function up(): void
    {
        // This migration is a one-shot "full bootstrap" schema.
        // In normal app evolution, dedicated migrations already create core tables.
        // Skip to avoid duplicate table errors during test migrations.
        if (Schema::hasTable('users')) {
            return;
        }

        // 1. Create Users Table (Enhanced)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['student', 'teacher', 'institute', 'admin'])->default('student');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('profile_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('preferences')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            $table->index(['role', 'is_active']);
            $table->index(['city', 'state']);
        });

        // 2. Authentication Tables
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // 3. Roles System
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'role_id']);
        });

        // 4. Subjects and Categories
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('exam_boards')->nullable(); // CBSE, ICSE, State boards
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index('slug');
        });

        // 5. Exams System
        Schema::create('exam_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('exam_categories')->onDelete('cascade');
            $table->string('type')->default('competitive'); // competitive, board, university
            $table->string('level')->nullable(); // school, undergraduate, postgraduate
            $table->date('exam_date')->nullable();
            $table->string('conducting_body')->nullable();
            $table->string('website')->nullable();
            $table->json('eligibility')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
        });

        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->boolean('is_mandatory')->default(false);
            $table->timestamps();
            
            $table->unique(['exam_id', 'subject_id']);
        });

        // 6. Institutes Table
        Schema::create('institutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('institute_name');
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('institute_type')->default('coaching'); // school, college, coaching, university
            $table->string('registration_number')->unique()->nullable();
            $table->string('website')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('pincode');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('facilities')->nullable();
            $table->json('working_hours')->nullable();
            $table->integer('established_year')->nullable();
            $table->integer('total_students')->default(0);
            $table->integer('total_teachers')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->boolean('verified')->default(false);
            $table->string('verification_status')->default('pending'); // pending, verified, rejected
            $table->boolean('is_featured')->default(false);
            $table->string('logo')->nullable();
            $table->json('gallery_images')->nullable();
            $table->text('specialization')->nullable();
            $table->string('affiliation')->nullable();
            $table->foreignId('parent_institute_id')->nullable()->constrained('institutes')->onDelete('set null');
            $table->string('branch_code')->nullable();
            $table->boolean('is_main_branch')->default(true);
            $table->timestamps();
            
            $table->index(['city', 'state', 'is_active' => 'verified']);
            $table->index(['institute_type', 'verified']);
            $table->index('slug');
        });

        // 7. Teacher Profiles Table
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('institute_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained('institutes')->onDelete('set null');
            $table->string('slug')->nullable()->unique();
            $table->string('qualification')->nullable();
            $table->text('qualifications')->nullable(); // Detailed qualifications
            $table->text('bio')->nullable();
            $table->integer('experience_years')->default(0);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();
            $table->string('specialization')->nullable();
            $table->json('languages')->nullable();
            $table->string('availability')->nullable();
            $table->enum('teaching_mode', ['online', 'offline', 'both'])->default('both');
            $table->boolean('online_classes')->default(true);
            $table->boolean('home_tuition')->default(true);
            $table->boolean('institute_classes')->default(false);
            $table->integer('travel_radius_km')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('teaching_city')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_students')->default(0);
            $table->integer('total_reviews')->default(0);
            $table->boolean('verified')->default(false);
            $table->string('verification_status')->default('pending'); // pending, verified, rejected
            $table->string('availability_status')->default('available'); // available, busy, unavailable
            $table->boolean('is_featured')->default(false);
            $table->string('avatar')->nullable();
            $table->json('teaching_preferences')->nullable();
            $table->json('certifications')->nullable();
            $table->timestamps();
            
            $table->index(['city', 'state', 'verified']);
            $table->index(['subject_id', 'verified']);
            $table->index('slug');
        });

        // 8. Student Profiles Table
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('student_id')->unique()->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('grade_level')->nullable(); // Class 1-12, Undergraduate, Postgraduate
            $table->string('school_name')->nullable();
            $table->string('board')->nullable(); // CBSE, ICSE, State Board
            $table->json('subjects_of_interest')->nullable();
            $table->json('learning_goals')->nullable();
            $table->string('learning_mode')->default('both'); // online, offline, both
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('parent_email')->nullable();
            $table->text('special_requirements')->nullable();
            $table->decimal('budget_min', 8, 2)->nullable();
            $table->decimal('budget_max', 8, 2)->nullable();
            $table->string('preferred_timing')->nullable();
            $table->integer('travel_radius_km')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('avatar')->nullable();
            $table->timestamps();
            
            $table->index(['grade_level', 'learning_mode']);
        });

        // 9. Relationship Tables
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teacher_profiles')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->string('grade_level')->nullable();
            $table->timestamps();
            
            $table->unique(['teacher_id', 'subject_id']);
        });

        Schema::create('institute_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->json('grade_levels')->nullable();
            $table->timestamps();
            
            $table->unique(['institute_id', 'subject_id']);
        });

        Schema::create('teacher_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teacher_profiles')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->json('specialization_areas')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->timestamps();
            
            $table->unique(['teacher_id', 'exam_id']);
        });

        Schema::create('institute_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->json('courses_offered')->nullable();
            $table->decimal('success_rate', 5, 2)->nullable();
            $table->integer('batch_size')->nullable();
            $table->timestamps();
            
            $table->unique(['institute_id', 'exam_id']);
        });

        // 10. School Branches and Hierarchies
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->string('branch_name');
            $table->string('branch_code')->unique();
            $table->string('branch_type')->default('branch'); // main, branch, affiliated
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('pincode');
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->json('facilities')->nullable();
            $table->integer('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['institute_id', 'is_active']);
        });

        Schema::create('school_branch_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->json('grade_levels')->nullable();
            $table->timestamps();
            
            $table->unique(['branch_id', 'subject_id']);
        });

        Schema::create('school_branch_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->json('preparation_details')->nullable();
            $table->timestamps();
            
            $table->unique(['branch_id', 'exam_id']);
        });

        // 11. Content Management
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery_images')->nullable();
            $table->enum('status', ['draft', 'published', 'private'])->default('draft');
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_comments')->default(true);
            $table->json('seo_data')->nullable();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'category']);
            $table->index(['is_featured', 'published_at']);
            $table->index('slug');
        });

        // 13. CRM and Leads
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_id')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->enum('type', ['student', 'parent', 'institute', 'teacher'])->default('student');
            $table->enum('source', ['website', 'social_media', 'referral', 'advertisement', 'direct'])->default('website');
            $table->string('subject_interest')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('location')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'converted', 'closed'])->default('new');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->json('contact_history')->nullable();
            $table->json('notes')->nullable();
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'priority']);
            $table->index(['type', 'source']);
            $table->index('phone');
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_id')->unique();
            $table->foreignId('student_id')->nullable()->constrained('student_profiles')->onDelete('set null');
            $table->foreignId('teacher_id')->nullable()->constrained('teacher_profiles')->onDelete('set null');
            $table->foreignId('institute_id')->nullable()->constrained()->onDelete('set null');
            $table->string('student_name');
            $table->string('student_email')->nullable();
            $table->string('student_phone');
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->enum('inquiry_type', ['teacher', 'institute', 'course', 'general'])->default('teacher');
            $table->string('subject_required')->nullable();
            $table->string('grade_level')->nullable();
            $table->enum('learning_mode', ['online', 'offline', 'both'])->default('both');
            $table->string('location')->nullable();
            $table->decimal('budget_min', 8, 2)->nullable();
            $table->decimal('budget_max', 8, 2)->nullable();
            $table->text('specific_requirements')->nullable();
            $table->string('preferred_timing')->nullable();
            $table->enum('urgency', ['immediate', 'within_week', 'within_month', 'flexible'])->default('flexible');
            $table->enum('status', ['open', 'in_progress', 'matched', 'closed'])->default('open');
            $table->timestamp('preferred_start_date')->nullable();
            $table->json('matched_teachers')->nullable();
            $table->json('matched_institutes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('communication_log')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'inquiry_type']);
            $table->index(['subject_required', 'grade_level']);
        });

        // 14. Site Settings and Configuration
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, number, boolean, file, json, textarea, select
            $table->string('group')->default('general'); // general, cache, database, social, content, email, sms
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('validation_rules')->nullable();
            $table->json('options')->nullable(); // For select/radio options
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['group', 'is_active']);
        });

        // 15. Navigation and Menus

        // 16. Queue and Cache Tables
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraints
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('school_branch_exams');
        Schema::dropIfExists('school_branch_subjects');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('institute_exams');
        Schema::dropIfExists('teacher_exams');
        Schema::dropIfExists('institute_subjects');
        Schema::dropIfExists('teacher_subjects');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('teacher_profiles');
        Schema::dropIfExists('institutes');
        Schema::dropIfExists('exam_subjects');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('exam_categories');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
}; 