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
        Schema::create('branch_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('institutes')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teacher_profiles')->onDelete('cascade');
            $table->date('joining_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'resigned', 'terminated'])->default('active');
            $table->json('additional_data')->nullable();
            $table->timestamps();
            
            $table->unique(['branch_id', 'teacher_id']);
            $table->index(['branch_id', 'status']);
            $table->index(['teacher_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_teacher');
    }
};
