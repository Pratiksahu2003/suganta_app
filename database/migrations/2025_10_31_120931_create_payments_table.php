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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('reference_id')->nullable(); // Cashfree reference id
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('currency', 10)->default('INR');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['created','pending','success','failed','cancelled','refunded'])->default('created');
            $table->json('meta')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['user_id','status']);
        });

        // Optional: add foreign key in a separate statement to avoid issues if users table differs
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
