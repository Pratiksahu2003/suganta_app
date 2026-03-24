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
        if (! Schema::hasTable('cities') || ! Schema::hasColumn('cities', 'name')) {
            return;
        }

        Schema::table('cities', function (Blueprint $table) {
            $table->renameColumn('name', 'city_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('cities') || ! Schema::hasColumn('cities', 'city_name')) {
            return;
        }

        Schema::table('cities', function (Blueprint $table) {
            $table->renameColumn('city_name', 'name');
        });
    }
};
