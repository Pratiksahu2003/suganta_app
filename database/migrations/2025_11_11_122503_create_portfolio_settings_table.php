<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('portfolio_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        // Insert default settings
        DB::table('portfolio_settings')->insert([
            [
                'key' => 'free_max_images',
                'value' => '2',
                'type' => 'integer',
                'description' => 'Maximum images for free users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'free_max_files',
                'value' => '2',
                'type' => 'integer',
                'description' => 'Maximum files for free users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'premium_max_images',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Maximum images for premium users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'premium_max_files',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Maximum files for premium users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_settings');
    }
};
