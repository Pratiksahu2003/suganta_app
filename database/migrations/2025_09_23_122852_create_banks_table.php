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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name')->nullable();
            $table->text('description')->nullable();
            $table->string('bank_type')->default('commercial'); // commercial, public, private, cooperative, foreign
            $table->string('website')->nullable();
            $table->string('customer_care_number')->nullable();
            $table->string('email')->nullable();
            $table->text('headquarters_address')->nullable();
            $table->string('established_year')->nullable();
            $table->string('logo')->nullable();
            $table->json('education_loan_features')->nullable(); // Interest rates, processing fees, etc.
            $table->json('eligibility_criteria')->nullable();
            $table->json('required_documents')->nullable();
            $table->decimal('min_loan_amount', 15, 2)->nullable();
            $table->decimal('max_loan_amount', 15, 2)->nullable();
            $table->decimal('interest_rate_min', 5, 2)->nullable();
            $table->decimal('interest_rate_max', 5, 2)->nullable();
            $table->integer('repayment_period_years')->nullable();
            $table->decimal('processing_fee_percentage', 5, 2)->nullable();
            $table->boolean('pre_approval_available')->default(false);
            $table->boolean('online_application')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['bank_type', 'is_active']);
            $table->index(['is_featured', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};