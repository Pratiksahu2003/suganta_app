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
        Schema::table('institute_subjects', function (Blueprint $table) {
            $table->string('course_duration')->nullable()->after('grade_levels');
            $table->decimal('fees', 10, 2)->nullable()->after('course_duration');
            $table->text('course_description')->nullable()->after('fees');
            $table->string('teaching_mode')->nullable()->after('course_description');
            $table->integer('batch_size')->nullable()->after('teaching_mode');
            $table->string('timings')->nullable()->after('batch_size');
            $table->string('status')->nullable()->after('timings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institute_subjects', function (Blueprint $table) {
            $table->dropColumn([
                'course_duration',
                'fees',
                'course_description',
                'teaching_mode',
                'batch_size',
                'timings',
                'status',
            ]);
        });
    }
};
