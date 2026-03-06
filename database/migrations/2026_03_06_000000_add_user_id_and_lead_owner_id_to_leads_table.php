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
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('lead_id')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('leads', 'lead_owner_id')) {
                $table->foreignId('lead_owner_id')->nullable()->after('user_id')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'lead_owner_id')) {
                $table->dropForeign(['lead_owner_id']);
            }
            if (Schema::hasColumn('leads', 'user_id')) {
                $table->dropForeign(['user_id']);
            }
        });
    }
};
