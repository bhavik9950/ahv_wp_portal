<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->text('system_prompt');
            $table->string('model')->nullable(); // null = config default
            $table->unsignedSmallInteger('daily_reply_cap')->default(40);
            $table->json('handoff_keywords')->nullable();
            $table->text('fallback_message');
            $table->timestamps();

            $table->unique('organization_id');
        });

        Schema::create('bot_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('wa_phone');
            $table->string('wa_phone_hash', 64)->index();
            $table->string('mode')->default('bot'); // bot | human | paused
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_bot_reply_at')->nullable();
            $table->unsignedInteger('replies_today')->default(0);
            $table->date('replies_today_on')->nullable();
            $table->string('handoff_reason')->nullable();
            $table->timestamp('handoff_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'wa_phone']);
            $table->index(['organization_id', 'mode']);
        });

        Schema::create('bot_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('bot_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->string('role'); // user | assistant
            $table->text('content');
            $table->string('model')->nullable();
            $table->unsignedInteger('tokens_in')->nullable();
            $table->unsignedInteger('tokens_out')->nullable();
            $table->timestamps();

            $table->index(['bot_conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_messages');
        Schema::dropIfExists('bot_conversations');
        Schema::dropIfExists('bot_settings');
    }
};
