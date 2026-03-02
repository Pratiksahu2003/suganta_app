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
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->string('session_duration_formatted')->nullable();
            $table->string('last_activity_formatted')->nullable();
            $table->string('login_time_formatted')->nullable();
            $table->string('device_info_summary')->nullable();
            $table->string('location_summary')->nullable();
            $table->string('security_level')->nullable();
            $table->string('activity_status')->nullable();
            $table->string('session_age')->nullable();
            $table->boolean('is_suspicious')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'session_duration_formatted',
                'last_activity_formatted',
                'login_time_formatted',
                'device_info_summary',
                'location_summary',
                'security_level',
                'activity_status',
                'session_age',
                'is_suspicious',
            ]);
        });
    }
}; 