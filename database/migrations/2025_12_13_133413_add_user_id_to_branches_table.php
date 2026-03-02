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
        Schema::table('branches', function (Blueprint $table) {
            // Drop the foreign key constraint on institute_id
            $table->dropForeign(['institute_id']);
            
            // Add user_id column
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            
            // Make institute_id nullable (keep for backward compatibility)
            $table->unsignedBigInteger('institute_id')->nullable()->change();
            
            // Add index for user_id
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Drop user_id foreign key and column
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropColumn('user_id');
            
            // Restore institute_id foreign key
            $table->unsignedBigInteger('institute_id')->nullable(false)->change();
            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
        });
    }
};
