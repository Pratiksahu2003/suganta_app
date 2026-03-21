<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->table('chat_conversation_participants', function (Blueprint $table): void {
            $table->unsignedBigInteger('last_read_message_id')->nullable()->after('left_at');
            $table->timestamp('muted_at')->nullable()->after('last_read_message_id');
            $table->timestamp('archived_at')->nullable()->after('muted_at');

            $table->foreign('last_read_message_id')
                ->references('id')
                ->on('chat_messages')
                ->nullOnDelete();

            $table->index(['user_id', 'archived_at', 'left_at'], 'ccp_user_archived_left_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->table('chat_conversation_participants', function (Blueprint $table): void {
            $table->dropForeign(['last_read_message_id']);
            $table->dropIndex('ccp_user_archived_left_idx');
            $table->dropColumn(['last_read_message_id', 'muted_at', 'archived_at']);
        });
    }
};
