<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Update conversations table to add missing enum values
        Schema::table('conversations', function (Blueprint $table) use ($driver) {
            // Add missing enum values to type column
            if ($driver !== 'sqlite' && Schema::hasColumn('conversations', 'type')) {
                $table->enum('type', ['general', 'support', 'group', 'student_teacher', 'student_institute', 'teacher_institute', 'admin_user'])
                    ->default('general')
                    ->change();
            }
        });

        // Update messages table to add missing fields and enum values
        Schema::table('messages', function (Blueprint $table) use ($driver) {
            if (! Schema::hasColumn('messages', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('read_at');
            }

            // sqlite does not support enum "change" in the same way; skip unsafe alter in tests/dev sqlite.
            if ($driver !== 'sqlite' && Schema::hasColumn('messages', 'type')) {
                $table->enum('type', ['text', 'image', 'file', 'audio', 'video', 'system'])
                    ->default('text')
                    ->change();
            }

            if ($driver !== 'sqlite' && Schema::hasColumn('messages', 'status')) {
                $table->enum('status', ['sending', 'sent', 'delivered', 'read'])
                    ->default('sent')
                    ->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        // Revert conversations table
        Schema::table('conversations', function (Blueprint $table) use ($driver) {
            if ($driver !== 'sqlite' && Schema::hasColumn('conversations', 'type')) {
                $table->enum('type', ['student_teacher', 'student_institute', 'teacher_institute', 'admin_user', 'general'])
                    ->default('general')
                    ->change();
            }
        });

        // Revert messages table
        Schema::table('messages', function (Blueprint $table) use ($driver) {
            if (Schema::hasColumn('messages', 'edited_at')) {
                $table->dropColumn('edited_at');
            }

            if ($driver !== 'sqlite' && Schema::hasColumn('messages', 'type')) {
                $table->enum('type', ['text', 'image', 'file', 'system'])
                    ->default('text')
                    ->change();
            }

            if ($driver !== 'sqlite' && Schema::hasColumn('messages', 'status')) {
                $table->enum('status', ['sent', 'delivered', 'read'])
                    ->default('sent')
                    ->change();
            }
        });
    }
};
