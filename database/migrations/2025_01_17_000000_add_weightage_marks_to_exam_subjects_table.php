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
        if (! Schema::hasTable('exam_subjects')) {
            return;
        }

        Schema::table('exam_subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_subjects', 'weightage')) {
                $table->integer('weightage')->nullable()->after('is_mandatory');
            }
            if (! Schema::hasColumn('exam_subjects', 'marks')) {
                $table->integer('marks')->nullable()->after('weightage');
            }
            if (! Schema::hasColumn('exam_subjects', 'is_optional')) {
                $table->boolean('is_optional')->default(false)->after('marks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('exam_subjects')) {
            return;
        }

        Schema::table('exam_subjects', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('exam_subjects', 'weightage')) {
                $drop[] = 'weightage';
            }
            if (Schema::hasColumn('exam_subjects', 'marks')) {
                $drop[] = 'marks';
            }
            if (Schema::hasColumn('exam_subjects', 'is_optional')) {
                $drop[] = 'is_optional';
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
}; 