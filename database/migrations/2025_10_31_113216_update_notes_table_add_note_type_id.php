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
        Schema::table('notes', function (Blueprint $table) {
            // Add note_type_id column
            $table->foreignId('note_type_id')->nullable()->after('name')->constrained('note_types')->onDelete('set null');
            
            // Keep type column for backward compatibility during migration
            // We'll remove it later after data migration if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign(['note_type_id']);
            $table->dropColumn('note_type_id');
        });
    }
};
