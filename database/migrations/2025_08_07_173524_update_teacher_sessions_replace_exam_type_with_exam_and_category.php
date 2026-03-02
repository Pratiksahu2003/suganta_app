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
        Schema::table('teacher_sessions', function (Blueprint $table) {
            // Drop existing columns if they exist
            if (Schema::hasColumn('teacher_sessions', 'exam_type_id')) {
                $table->dropColumn('exam_type_id');
            }
            if (Schema::hasColumn('teacher_sessions', 'category')) {
                $table->dropColumn('category');
            }
            
            // Add new columns
            if (!Schema::hasColumn('teacher_sessions', 'exam_id')) {
                $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('set null');
            }
            if (!Schema::hasColumn('teacher_sessions', 'exam_category_id')) {
                $table->foreignId('exam_category_id')->nullable()->constrained('exam_categories')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_sessions', function (Blueprint $table) {
            // Drop new columns
            if (Schema::hasColumn('teacher_sessions', 'exam_id')) {
                $table->dropForeign(['exam_id']);
                $table->dropColumn('exam_id');
            }
            if (Schema::hasColumn('teacher_sessions', 'exam_category_id')) {
                $table->dropForeign(['exam_category_id']);
                $table->dropColumn('exam_category_id');
            }
            
            // Restore old columns
            $table->foreignId('exam_type_id')->nullable()->constrained('exam_types')->onDelete('set null');
            $table->string('category')->nullable();
        });
    }
};
