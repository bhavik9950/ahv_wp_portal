<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('whatsapp_phone_number_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('campaign_recipient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained()->nullOnDelete();

            $table->string('direction')->default('outbound'); // outbound | inbound
            $table->string('to_phone');
            $table->string('to_phone_hash', 64)->index();
            $table->string('type'); // text|template|image|video|document|audio|location|interactive
            $table->foreignUlid('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();

            $table->json('payload')->nullable();       // rendered message sent to Meta (no secrets)
            $table->string('wamid')->nullable()->unique();
            $table->string('idempotency_key')->unique();

            $table->string('status')->default('pending'); // pending|queued|processing|sent|delivered|read|failed|cancelled|skipped
            $table->string('error_code')->nullable();
            $table->string('error_category')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'created_at']);
            $table->index(['campaign_id', 'status']);
        });

        // Append-only. Rows are never mutated (no updated_at).
        Schema::create('message_status_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('message_id')->constrained()->cascadeOnDelete();
            $table->string('wamid')->nullable();
            $table->string('status');
            $table->string('error_code')->nullable();
            $table->string('error_title')->nullable();
            $table->text('error_message')->nullable();
            $table->ulid('webhook_event_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['message_id', 'status']);
            $table->unique(['message_id', 'status', 'occurred_at'], 'msg_status_dedupe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_status_events');
        Schema::dropIfExists('messages');
    }
};
