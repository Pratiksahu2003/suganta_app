<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_seo_contents', function (Blueprint $table) {
            $table->id();
            $table->integer('city_id'); // Changed from unsignedBigInteger to integer to match cities.id
            $table->string('slug')->unique();
            $table->string('seo_title');
            $table->text('meta_description');
            $table->text('meta_keywords');
            $table->text('hero_title');
            $table->text('hero_description');
            $table->text('education_overview_title');
            $table->text('education_overview_description');
            $table->text('services_title');
            $table->text('services_description');
            $table->text('how_it_works_title');
            $table->text('how_it_works_description');
            $table->text('popular_spots_title');
            $table->text('popular_spots_description');
            $table->text('faq_title');
            $table->text('faq_description');
            $table->text('conclusion_title');
            $table->text('conclusion_description');
            $table->json('popular_education_spots')->nullable();
            $table->json('faq_items')->nullable();
            $table->json('how_it_works_steps')->nullable();
            $table->string('video_source')->default('\\video\\seo.mp4');
            $table->text('map_embed_url');
            $table->string('map_legend_college')->default('College/Schools');
            $table->string('map_legend_coaching')->default('Coaching Hubs');
            $table->string('map_legend_tuition')->default('Home Tuition Zones');
            $table->integer('key_areas_count')->default(0);
            $table->integer('districts_count')->default(0);
            $table->string('start_point');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['city_id', 'is_active']);
            $table->index('slug');
            
            // Add foreign key constraint with matching data type
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_seo_contents');
    }
};
