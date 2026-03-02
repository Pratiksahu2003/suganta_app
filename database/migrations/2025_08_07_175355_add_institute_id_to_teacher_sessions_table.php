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
            if (!Schema::hasColumn('teacher_sessions', 'institute_id')) {
                $table->foreignId('institute_id')->nullable()->constrained('institutes')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_sessions', 'institute_id')) {
                $table->dropForeign(['institute_id']);
                $table->dropColumn('institute_id');
            }
        });
    }
};
