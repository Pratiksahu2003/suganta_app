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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('company_name');
            $table->string('company_logo')->nullable();
            $table->string('location');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');
            $table->boolean('remote_work')->default(false);
            $table->enum('job_type', ['full-time', 'part-time', 'contract', 'internship', 'freelance'])->default('full-time');
            $table->enum('experience_level', ['entry', 'mid', 'senior', 'executive'])->default('entry');
            $table->enum('education_level', ['high-school', 'diploma', 'bachelor', 'master', 'phd'])->nullable();
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('salary_currency', 3)->default('INR');
            $table->enum('salary_period', ['hourly', 'daily', 'weekly', 'monthly', 'yearly'])->default('monthly');
            $table->longText('description');
            $table->json('requirements')->nullable();
            $table->json('responsibilities')->nullable();
            $table->json('benefits')->nullable();
            $table->json('skills')->nullable();
            $table->string('industry')->nullable();
            $table->string('department')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->timestamp('application_deadline')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_urgent')->default(false);
            $table->integer('views_count')->default(0);
            $table->integer('applications_count')->default(0);
            $table->foreignId('posted_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('seo_friendly_url')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'closed'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'is_active']);
            $table->index(['job_type', 'experience_level']);
            $table->index(['location', 'city', 'state']);
            $table->index(['industry', 'department']);
            $table->index(['salary_min', 'salary_max']);
            $table->index(['is_featured', 'is_urgent']);
            $table->index(['application_deadline', 'status']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
