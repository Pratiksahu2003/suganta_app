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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->decimal('total_earned', 12, 2)->default(0.00); // Total amount ever earned
            $table->decimal('total_withdrawn', 12, 2)->default(0.00); // Total amount withdrawn
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });

        // Create wallet transactions table to track all wallet activities
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedBigInteger('user_id'); // Owner of the wallet
            $table->enum('type', ['credit', 'debit']); // credit = money added, debit = money withdrawn
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2); // Balance before transaction
            $table->decimal('balance_after', 12, 2); // Balance after transaction
            $table->string('transaction_type')->nullable(); // 'class_booking', 'withdrawal', 'refund', etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of related record (payment_id, enrollment_id, etc.)
            $table->string('reference_type')->nullable(); // Model class name (Payment, SessionEnrollment, etc.)
            $table->text('description')->nullable();
            $table->json('meta')->nullable(); // Additional metadata
            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['wallet_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
