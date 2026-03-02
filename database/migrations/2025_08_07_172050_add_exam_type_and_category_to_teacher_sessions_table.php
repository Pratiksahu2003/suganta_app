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
            if (!Schema::hasColumn('teacher_sessions', 'exam_type_id')) {
                $table->foreignId('exam_type_id')->nullable()->constrained('exam_types')->onDelete('set null');
            }
            if (!Schema::hasColumn('teacher_sessions', 'category')) {
                $table->string('category')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_sessions', 'exam_type_id')) {
                $table->dropForeign(['exam_type_id']);
                $table->dropColumn('exam_type_id');
            }
            if (Schema::hasColumn('teacher_sessions', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
