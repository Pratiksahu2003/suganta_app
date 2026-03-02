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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('order_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('donor_name')->nullable();
            $table->string('donor_email')->nullable();
            $table->string('donor_phone')->nullable();
            $table->string('currency', 10)->default('INR');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['created', 'pending', 'success', 'failed', 'cancelled'])->default('created');
            $table->text('message')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('source')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['order_id', 'status']);
        });

        Schema::table('donations', function (Blueprint $table) {
            if (Schema::hasTable('payments')) {
                $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
            }
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
        Schema::dropIfExists('donations');
    }
};
