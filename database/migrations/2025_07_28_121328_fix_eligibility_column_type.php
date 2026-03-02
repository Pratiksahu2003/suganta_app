<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Change eligibility column from JSON to text
            $table->text('eligibility')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Change back to JSON if needed
            $table->json('eligibility')->nullable()->change();
        });
    }
}; 