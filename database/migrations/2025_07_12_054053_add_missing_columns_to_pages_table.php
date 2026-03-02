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
        Schema::table('pages', function (Blueprint $table) {
            // Add only the missing columns that don't exist in the main migration
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->string('password')->nullable()->after('status');
            $table->boolean('featured')->default(false)->after('password');
            $table->integer('menu_order')->default(0)->after('show_in_menu');
            $table->string('template')->nullable()->after('menu_order');
            $table->timestamp('expires_at')->nullable()->after('published_at');
            $table->boolean('allow_comments')->default(false)->after('expires_at');
            $table->boolean('allow_ratings')->default(false)->after('allow_comments');
            $table->boolean('is_homepage')->default(false)->after('allow_ratings');
            $table->text('custom_css')->nullable()->after('is_homepage');
            $table->text('custom_js')->nullable()->after('custom_css');
            $table->unsignedBigInteger('updated_by')->nullable()->after('author_id');
            
            // Add foreign key constraint for updated_by
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['updated_by']);
            
            // Drop only the columns that were added
            $table->dropColumn([
                'meta_keywords', 'password', 'featured', 'menu_order', 'template',
                'expires_at', 'allow_comments', 'allow_ratings', 'is_homepage',
                'custom_css', 'custom_js', 'updated_by'
            ]);
        });
    }
};
