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
        Schema::create('study_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('contact_role', ['student', 'parent'])->default('student');
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->boolean('is_contact_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('student_name')->nullable();
            $table->string('student_grade')->nullable();
            $table->json('subjects')->nullable();
            $table->enum('learning_mode', ['online', 'offline', 'both'])->default('both');
            $table->string('preferred_days')->nullable();
            $table->string('preferred_time')->nullable();
            $table->string('location_city')->nullable();
            $table->string('location_state')->nullable();
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->text('requirements')->nullable();
            $table->enum('status', ['new', 'in_review', 'matched', 'closed'])->default('new');
            $table->json('meta')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['status', 'contact_role']);
            $table->index('contact_phone');
            $table->index('contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_requirements');
    }
};

