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
        Schema::create('session_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id');
            $table->foreignId('student_id');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->enum('status', ['enrolled', 'attended', 'no_show', 'cancelled'])->default('enrolled');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->decimal('amount_paid', 8, 2)->nullable();
            $table->integer('rating')->nullable(); // 1-5 stars
            $table->text('review')->nullable();
            $table->timestamps();
            
            $table->unique(['session_id', 'student_id']);
            $table->index(['student_id', 'status']);
            $table->index(['session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_enrollments');
    }
}; 