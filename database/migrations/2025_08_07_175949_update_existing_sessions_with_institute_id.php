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
        // Update sessions that have null institute_id but have a teacher_profile_id
        DB::statement("
            UPDATE teacher_sessions 
            SET institute_id = (
                SELECT institute_id 
                FROM teacher_profiles 
                WHERE teacher_profiles.id = teacher_sessions.teacher_profile_id
            )
            WHERE teacher_sessions.institute_id IS NULL 
            AND teacher_sessions.teacher_profile_id IS NOT NULL
        ");

        // For sessions without teachers, we'll need to handle them differently
        // For now, we'll set them to a default institute or leave them null
        // This can be handled in the application logic
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for this data migration
    }
};
