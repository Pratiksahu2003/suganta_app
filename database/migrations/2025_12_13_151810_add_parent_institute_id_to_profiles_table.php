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
        Schema::table('profiles', function (Blueprint $table) {
            // Add parent_institute_id column to link teacher profiles to institute profiles
            $table->foreignId('parent_institute_id')
                ->nullable()
                ->after('user_id')
                ->constrained('profiles')
                ->onDelete('set null');
            
            // Add index for better query performance
            $table->index('parent_institute_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeign(['parent_institute_id']);
            $table->dropIndex(['parent_institute_id']);
            $table->dropColumn('parent_institute_id');
        });
    }
};
