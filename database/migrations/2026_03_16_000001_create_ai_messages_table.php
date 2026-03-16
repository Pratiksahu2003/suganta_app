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
        Schema::connection('ai_mysql')->create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->string('role');
            $table->unsignedBigInteger('prompt_tokens')->default(0);
            $table->unsignedBigInteger('completion_tokens')->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->unique('message_id');
            $table->index('role');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('ai_mysql')->dropIfExists('ai_messages');
    }
};

