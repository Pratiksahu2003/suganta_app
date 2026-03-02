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
        // Drop existing bank_seo_contents table if it exists
        Schema::dropIfExists('bank_seo_contents');
        
        // Create bank_seo_contents table with proper structure
        Schema::create('bank_seo_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id');
            $table->string('slug')->unique();
            $table->string('seo_title');
            $table->text('meta_description');
            $table->text('meta_keywords')->nullable();
            $table->string('hero_title');
            $table->text('hero_description');
            $table->string('loan_overview_title');
            $table->text('loan_overview_description');
            $table->string('features_title');
            $table->text('features_description');
            $table->string('eligibility_title');
            $table->text('eligibility_description');
            $table->string('documents_title');
            $table->text('documents_description');
            $table->string('process_title');
            $table->text('process_description');
            $table->string('faq_title');
            $table->text('faq_description');
            $table->string('conclusion_title');
            $table->text('conclusion_description');
            $table->json('loan_features');
            $table->json('eligibility_points');
            $table->json('required_documents');
            $table->json('application_process_steps');
            $table->json('faq_items');
            $table->json('comparison_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['bank_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_seo_contents');
    }
};