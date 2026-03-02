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
            $table->string('demo_video')->nullable()->after('description');
            $table->text('meeting_link')->nullable()->after('location');
            $table->text('additional_info')->nullable()->after('notes');
            $table->string('google_meet_event_id')->nullable()->after('meeting_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_sessions', function (Blueprint $table) {
            $table->dropColumn(['demo_video', 'meeting_link', 'additional_info', 'google_meet_event_id']);
        });
    }
};
