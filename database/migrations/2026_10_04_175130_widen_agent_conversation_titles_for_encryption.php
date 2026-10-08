<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table($this->messagesTable())->delete();
        DB::table($this->conversationsTable())->delete();

        Schema::table($this->conversationsTable(), function (Blueprint $table) {
            $table->text('title')->change();
        });
    }

    public function down(): void
    {
        DB::table($this->messagesTable())->delete();
        DB::table($this->conversationsTable())->delete();

        Schema::table($this->conversationsTable(), function (Blueprint $table) {
            $table->string('title')->change();
        });
    }

    private function conversationsTable(): string
    {
        return config('ai.conversations.tables.conversations', 'agent_conversations');
    }

    private function messagesTable(): string
    {
        return config('ai.conversations.tables.messages', 'agent_conversation_messages');
    }
};
