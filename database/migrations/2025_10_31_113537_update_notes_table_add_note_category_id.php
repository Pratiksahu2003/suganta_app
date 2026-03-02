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
            // Add note_category_id column
            $table->foreignId('note_category_id')->nullable()->after('note_type_id')->constrained('note_categories')->onDelete('set null');
            
            // Keep category column for backward compatibility during migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign(['note_category_id']);
            $table->dropColumn('note_category_id');
        });
    }
};
