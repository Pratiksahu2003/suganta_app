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
        // Update conversations table to add missing enum values
        Schema::table('conversations', function (Blueprint $table) {
            // Add missing enum values to type column
            $table->enum('type', ['general', 'support', 'group', 'student_teacher', 'student_institute', 'teacher_institute', 'admin_user'])
                  ->default('general')
                  ->change();
        });

        // Update messages table to add missing fields and enum values
        Schema::table('messages', function (Blueprint $table) {
            // Add edited_at field
            $table->timestamp('edited_at')->nullable()->after('read_at');
            
            // Add missing enum values to type column
            $table->enum('type', ['text', 'image', 'file', 'audio', 'video', 'system'])
                  ->default('text')
                  ->change();
            
            // Add missing enum values to status column
            $table->enum('status', ['sending', 'sent', 'delivered', 'read'])
                  ->default('sent')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert conversations table
        Schema::table('conversations', function (Blueprint $table) {
            $table->enum('type', ['student_teacher', 'student_institute', 'teacher_institute', 'admin_user', 'general'])
                  ->default('general')
                  ->change();
        });

        // Revert messages table
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('edited_at');
            
            $table->enum('type', ['text', 'image', 'file', 'system'])
                  ->default('text')
                  ->change();
            
            $table->enum('status', ['sent', 'delivered', 'read'])
                  ->default('sent')
                  ->change();
        });
    }
};
