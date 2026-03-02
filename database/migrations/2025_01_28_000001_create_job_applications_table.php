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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('applicant_id')->constrained('users')->onDelete('cascade');
            $table->text('cover_letter')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('resume_filename')->nullable();
            $table->decimal('expected_salary', 10, 2)->nullable();
            $table->date('availability_date')->nullable();
            $table->enum('status', [
                'pending', 
                'reviewed', 
                'shortlisted', 
                'interviewed', 
                'offered', 
                'rejected', 
                'withdrawn'
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('interview_date')->nullable();
            $table->string('interview_location')->nullable();
            $table->text('interview_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->boolean('is_shortlisted')->default(false);
            $table->timestamp('shortlisted_at')->nullable();
            $table->foreignId('shortlisted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['job_id', 'status']);
            $table->index(['applicant_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['is_shortlisted', 'status']);
            $table->index(['interview_date', 'status']);
            $table->unique(['job_id', 'applicant_id']); // Prevent duplicate applications
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
