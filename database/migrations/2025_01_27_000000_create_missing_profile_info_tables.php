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
        // Create profile_teaching_info table if it doesn't exist
        if (!Schema::hasTable('profile_teaching_info')) {
            Schema::create('profile_teaching_info', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained()->onDelete('cascade');
                $table->integer('teaching_experience_years')->default(0);
                $table->integer('total_students_taught')->default(0);
                $table->decimal('hourly_rate', 8, 2)->nullable();
                $table->decimal('monthly_rate', 8, 2)->nullable();
                $table->string('teaching_mode')->default('both'); // online, offline, both
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
                $table->string('availability_status')->default('available'); // available, busy, unavailable
                $table->timestamps();
                
                $table->index(['profile_id', 'teaching_mode']);
                $table->index('availability_status');
            });
        }

        // Create profile_professional_info table if it doesn't exist
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

        // Create profile_social_links table if it doesn't exist
        if (!Schema::hasTable('profile_social_links')) {
            Schema::create('profile_social_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained()->onDelete('cascade');
                $table->string('platform'); // facebook, twitter, instagram, linkedin, youtube, etc.
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
        Schema::dropIfExists('profile_social_links');
        Schema::dropIfExists('profile_professional_info');
        Schema::dropIfExists('profile_teaching_info');
    }
}; 