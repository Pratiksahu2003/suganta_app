<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legacy chat schema is removed in favor of V3 chat tables:
     * chat_conversations, chat_conversation_participants, chat_messages,
     * chat_message_reads, chat_message_reactions.
     */
    public function up(): void
    {
        $connections = array_unique([
            config('database.default'),
            'ai_mysql',
        ]);

        foreach ($connections as $connection) {
            if (! is_string($connection) || $connection === '') {
                continue;
            }

            $schema = Schema::connection($connection);

            $schema->dropIfExists('message_reactions');
            $schema->dropIfExists('blocked_users');
            $schema->dropIfExists('user_blocks');
            $schema->dropIfExists('messages');
            $schema->dropIfExists('conversations');
        }
    }

    public function down(): void
    {
        // Intentionally left blank because legacy schema should not be restored.
    }
};

