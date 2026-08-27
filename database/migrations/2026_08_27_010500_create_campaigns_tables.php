<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft'); // draft|scheduled|processing|paused|completed|cancelled|failed

            $table->foreignUlid('whatsapp_phone_number_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
            $table->json('variable_map')->nullable();
            $table->foreignUlid('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('audience_filter')->nullable();

            $table->unsignedInteger('send_delay_seconds')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('timezone')->default('UTC');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('totals')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained()->nullOnDelete();

            $table->string('phone_e164');
            $table->json('rendered_variables')->nullable();
            $table->string('status')->default('pending'); // pending|queued|sent|delivered|read|failed|skipped|opted_out
            $table->ulid('message_id')->nullable();
            $table->string('skip_reason')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'phone_e164']);
            $table->index(['campaign_id', 'status']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('whatsapp_phone_number_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained()->nullOnDelete();

            $table->string('to_phone');
            $table->string('type');
            $table->json('payload')->nullable();
            $table->foreignUlid('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
            $table->foreignUlid('media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->timestamp('send_at');
            $table->string('status')->default('pending'); // pending|queued|sent|cancelled|failed
            $table->ulid('message_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'send_at']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_messages');
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
    }
};
