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
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('images')->nullable(); // Array of image paths
            $table->json('files')->nullable(); // Array of file paths
            $table->string('category')->nullable();
            $table->string('tags')->nullable(); // Comma-separated tags
            $table->string('url')->nullable(); // External portfolio URL
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('order')->default(0); // For ordering portfolios
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
