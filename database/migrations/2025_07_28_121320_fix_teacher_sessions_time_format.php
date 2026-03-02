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
        // Fix any existing time values that might be in wrong format
        $sessions = DB::table('teacher_sessions')->get();
        
        foreach ($sessions as $session) {
            $time = $session->time;
            
            // If time is in H:i format (5 characters), add seconds
            if (strlen($time) === 5 && strpos($time, ':') !== false) {
                $time .= ':00';
                DB::table('teacher_sessions')
                    ->where('id', $session->id)
                    ->update(['time' => $time]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this migration
    }
}; 