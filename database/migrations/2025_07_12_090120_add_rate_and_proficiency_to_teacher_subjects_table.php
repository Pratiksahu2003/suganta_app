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
        Schema::table('teacher_subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_subjects', 'subject_rate')) {
                $table->decimal('subject_rate', 8, 2)->nullable()->after('grade_level');
            }
            if (!Schema::hasColumn('teacher_subjects', 'proficiency_level')) {
                $table->string('proficiency_level')->nullable()->after('subject_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_subjects', 'subject_rate')) {
                $table->dropColumn('subject_rate');
            }
            if (Schema::hasColumn('teacher_subjects', 'proficiency_level')) {
                $table->dropColumn('proficiency_level');
            }
        });
    }
};
