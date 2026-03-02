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
        // Handle existing NULL phone values before making it required
        // Set a temporary unique value for NULL phones to avoid constraint issues
        \DB::table('users')
            ->whereNull('phone')
            ->orWhere('phone', '')
            ->update(['phone' => \DB::raw("CONCAT('temp_', id, '_', UNIX_TIMESTAMP())")]);
        
        Schema::table('users', function (Blueprint $table) {
            // Make phone required and unique
            $table->string('phone')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->string('phone')->nullable()->change();
        });
    }
};
