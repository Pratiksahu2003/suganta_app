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
        Schema::table('payments', function (Blueprint $table) {
            // Used to make downstream processing idempotent (webhook/callback retries, double submits, etc.)
            if (!Schema::hasColumn('payments', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'processed_at')) {
                $table->dropIndex(['processed_at']);
                $table->dropColumn('processed_at');
            }
        });
    }
};

