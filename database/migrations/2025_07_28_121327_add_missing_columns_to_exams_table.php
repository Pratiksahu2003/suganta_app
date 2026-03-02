<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Add all potentially missing columns
            if (!Schema::hasColumn('exams', 'conducting_body')) {
                $table->string('conducting_body')->nullable();
            }
            if (!Schema::hasColumn('exams', 'frequency')) {
                $table->string('frequency')->nullable();
            }
            if (!Schema::hasColumn('exams', 'eligibility')) {
                $table->text('eligibility')->nullable();
            }
            if (!Schema::hasColumn('exams', 'exam_pattern')) {
                $table->text('exam_pattern')->nullable();
            }
            if (!Schema::hasColumn('exams', 'syllabus')) {
                $table->text('syllabus')->nullable();
            }
            if (!Schema::hasColumn('exams', 'preparation_tips')) {
                $table->text('preparation_tips')->nullable();
            }
            if (!Schema::hasColumn('exams', 'official_website')) {
                $table->string('official_website')->nullable();
            }
            if (!Schema::hasColumn('exams', 'application_fee')) {
                $table->decimal('application_fee', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('exams', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable();
            }
            if (!Schema::hasColumn('exams', 'total_marks')) {
                $table->integer('total_marks')->nullable();
            }
            if (!Schema::hasColumn('exams', 'negative_marking')) {
                $table->boolean('negative_marking')->default(false);
            }
            if (!Schema::hasColumn('exams', 'status')) {
                $table->string('status')->default('active');
            }
            if (!Schema::hasColumn('exams', 'featured')) {
                $table->boolean('featured')->default(false);
            }
            if (!Schema::hasColumn('exams', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $columns = [
                'conducting_body', 'frequency', 'eligibility', 'exam_pattern',
                'syllabus', 'preparation_tips', 'official_website', 'application_fee',
                'duration_minutes', 'total_marks', 'negative_marking', 'status',
                'featured', 'sort_order'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('exams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}; 