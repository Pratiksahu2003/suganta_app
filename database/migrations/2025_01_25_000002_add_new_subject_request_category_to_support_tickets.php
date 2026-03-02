<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            // Update the category enum to include new_subject_request
            DB::statement("ALTER TABLE support_tickets MODIFY COLUMN category ENUM('technical', 'billing', 'account', 'subject', 'exam', 'new_subject_request', 'feature_request', 'bug_report', 'general') DEFAULT 'general'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            // Revert the category enum to remove new_subject_request
            DB::statement("ALTER TABLE support_tickets MODIFY COLUMN category ENUM('technical', 'billing', 'account', 'subject', 'exam', 'feature_request', 'bug_report', 'general') DEFAULT 'general'");
        });
    }
}; 