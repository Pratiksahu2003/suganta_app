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
        // First, update any existing 'minimal' values to 'basic'
        DB::table('profiles')
            ->where('profile_completion_status', 'minimal')
            ->update(['profile_completion_status' => 'basic']);

        // Then modify the enum to ensure it only contains the allowed values
        DB::statement("ALTER TABLE profiles MODIFY COLUMN profile_completion_status ENUM('basic', 'detailed', 'complete') DEFAULT 'basic'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original enum if needed
        DB::statement("ALTER TABLE profiles MODIFY COLUMN profile_completion_status ENUM('basic', 'detailed', 'complete', 'minimal') DEFAULT 'basic'");
    }
}; 