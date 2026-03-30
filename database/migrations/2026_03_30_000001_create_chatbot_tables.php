<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     */
    protected $connection = 'ai_mysql';

    public function up(): void
    {
        // ──────────────────────────────────────────────
        // 1. chatbot_users – platform users (IG / Messenger)
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_users', function (Blueprint $table) {
            $table->id();
            $table->string('platform_user_id', 100)->comment('Meta PSID / IGSID');
            $table->enum('platform', ['instagram', 'messenger'])->default('messenger');
            $table->string('name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('profile_pic_url', 500)->nullable();
            $table->string('locale', 20)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->string('block_reason', 255)->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->unique(['platform_user_id', 'platform'], 'cu_platform_user_unique');
            $table->index('is_blocked');
            $table->index('platform');
            $table->index('last_seen_at');
        });

        // ──────────────────────────────────────────────
        // 2. chatbot_conversations
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatbot_user_id');
            $table->enum('platform', ['instagram', 'messenger'])->default('messenger');
            $table->enum('status', ['bot', 'human', 'closed'])->default('bot');
            $table->unsignedBigInteger('assigned_admin_id')->nullable()->comment('FK to main DB users table');
            $table->string('subject', 255)->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('chatbot_user_id')->references('id')->on('chatbot_users')->cascadeOnDelete();

            // Indexes
            $table->index('status');
            $table->index('platform');
            $table->index(['chatbot_user_id', 'status'], 'cc_user_status');
            $table->index('last_message_at');
        });

        // ──────────────────────────────────────────────
        // 3. chatbot_faqs
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500);
            $table->text('answer');
            $table->string('category', 100)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('category');
            $table->fullText('question', 'cf_question_fulltext');
        });

        // ──────────────────────────────────────────────
        // 4. chatbot_keywords
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 100);
            $table->text('response');
            $table->string('category', 100)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->unique('keyword');
            $table->index('is_active');
        });

        // ──────────────────────────────────────────────
        // 5. chatbot_intents
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_intents', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->float('confidence_threshold')->default(0.6);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
            $table->index('is_active');
        });

        // ──────────────────────────────────────────────
        // 6. chatbot_intent_keywords
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_intent_keywords', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intent_id');
            $table->string('keyword', 100);
            $table->float('weight')->default(1.0);
            $table->timestamps();

            $table->foreign('intent_id')->references('id')->on('chatbot_intents')->cascadeOnDelete();
            $table->index(['intent_id', 'keyword'], 'cik_intent_keyword');
        });

        // ──────────────────────────────────────────────
        // 7. chatbot_intent_responses
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_intent_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intent_id');
            $table->text('response');
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('intent_id')->references('id')->on('chatbot_intents')->cascadeOnDelete();
            $table->index(['intent_id', 'is_active'], 'cir_intent_active');
        });

        // ──────────────────────────────────────────────
        // 8. chatbot_messages
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('chatbot_user_id')->nullable();
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->enum('message_type', ['text', 'image', 'quick_reply', 'template', 'fallback'])->default('text');
            $table->text('content');
            $table->json('raw_payload')->nullable();
            $table->string('matched_by', 30)->nullable()->comment('keyword|faq|intent|ai_gemini|ai_grok|manual|fallback');
            $table->unsignedBigInteger('matched_faq_id')->nullable();
            $table->unsignedBigInteger('matched_intent_id')->nullable();
            $table->string('meta_message_id', 255)->nullable()->comment('Message ID from Meta API');
            $table->enum('delivery_status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('conversation_id')->references('id')->on('chatbot_conversations')->cascadeOnDelete();
            $table->foreign('chatbot_user_id')->references('id')->on('chatbot_users')->nullOnDelete();
            $table->foreign('matched_faq_id')->references('id')->on('chatbot_faqs')->nullOnDelete();
            $table->foreign('matched_intent_id')->references('id')->on('chatbot_intents')->nullOnDelete();

            // Indexes
            $table->index(['conversation_id', 'direction'], 'cm_conv_direction');
            $table->index('direction');
            $table->index('matched_by');
            $table->index('meta_message_id');
            $table->index('created_at');
        });

        // ──────────────────────────────────────────────
        // 9. chatbot_message_logs
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_message_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatbot_user_id')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->enum('platform', ['instagram', 'messenger']);
            $table->string('event_type', 50)->comment('message_received|message_sent|delivery|read|postback|referral|error');
            $table->json('payload')->nullable();
            $table->string('processing_status', 20)->default('success')->comment('success|failed|skipped');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('chatbot_user_id')->references('id')->on('chatbot_users')->nullOnDelete();
            $table->foreign('conversation_id')->references('id')->on('chatbot_conversations')->nullOnDelete();

            // Indexes
            $table->index('event_type');
            $table->index('processing_status');
            $table->index('created_at');
            $table->index(['platform', 'event_type'], 'cml_platform_event');
        });

        // ──────────────────────────────────────────────
        // 10. chatbot_webhook_events
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['instagram', 'messenger']);
            $table->string('event_type', 50);
            $table->json('raw_payload');
            $table->string('processing_status', 20)->default('pending')->comment('pending|processed|failed');
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('processing_status');
            $table->index(['platform', 'event_type'], 'cwe_platform_event');
            $table->index('created_at');
        });

        // ──────────────────────────────────────────────
        // 11. chatbot_bot_settings
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_bot_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'boolean', 'integer', 'json'])->default('string');
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->unique('key');
        });

        // ──────────────────────────────────────────────
        // 12. chatbot_leads
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatbot_user_id');
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->enum('source', ['instagram', 'messenger']);
            $table->string('interest', 255)->nullable();
            $table->json('extra_data')->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'converted', 'lost'])->default('new');
            $table->timestamps();

            // Foreign keys
            $table->foreign('chatbot_user_id')->references('id')->on('chatbot_users')->cascadeOnDelete();
            $table->foreign('conversation_id')->references('id')->on('chatbot_conversations')->nullOnDelete();

            // Indexes
            $table->index('status');
            $table->index('source');
            $table->index('created_at');
        });

        // ──────────────────────────────────────────────
        // 13. chatbot_analytics
        // ──────────────────────────────────────────────
        Schema::connection($this->connection)->create('chatbot_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('platform', 20)->default('all')->comment('instagram|messenger|all');
            $table->unsignedInteger('total_messages_received')->default(0);
            $table->unsignedInteger('total_messages_sent')->default(0);
            $table->unsignedInteger('unique_users')->default(0);
            $table->unsignedInteger('new_users')->default(0);
            $table->unsignedInteger('keyword_matches')->default(0);
            $table->unsignedInteger('faq_matches')->default(0);
            $table->unsignedInteger('intent_matches')->default(0);
            $table->unsignedInteger('ai_fallbacks')->default(0);
            $table->unsignedInteger('no_matches')->default(0);
            $table->float('avg_response_time_ms')->default(0);
            $table->unsignedInteger('leads_captured')->default(0);
            $table->timestamps();

            // Indexes
            $table->unique(['date', 'platform'], 'ca_date_platform_unique');
            $table->index('date');
        });
    }

    public function down(): void
    {
        $tables = [
            'chatbot_analytics',
            'chatbot_leads',
            'chatbot_bot_settings',
            'chatbot_webhook_events',
            'chatbot_message_logs',
            'chatbot_messages',
            'chatbot_intent_responses',
            'chatbot_intent_keywords',
            'chatbot_intents',
            'chatbot_keywords',
            'chatbot_faqs',
            'chatbot_conversations',
            'chatbot_users',
        ];

        foreach ($tables as $table) {
            Schema::connection($this->connection)->dropIfExists($table);
        }
    }
};
