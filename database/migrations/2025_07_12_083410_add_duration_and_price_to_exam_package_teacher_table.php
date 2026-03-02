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
        Schema::table('exam_package_teacher', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_package_teacher', 'duration')) {
                $table->integer('duration')->nullable()->after('exam_package_id');
            }
            if (!Schema::hasColumn('exam_package_teacher', 'price')) {
                $table->decimal('price', 8, 2)->nullable()->after('duration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_package_teacher', function (Blueprint $table) {
            if (Schema::hasColumn('exam_package_teacher', 'duration')) {
                $table->dropColumn('duration');
            }
            if (Schema::hasColumn('exam_package_teacher', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
