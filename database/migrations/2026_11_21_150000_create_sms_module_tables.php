<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add SMS Wallet and Settings to Organizations table
        Schema::table('organizations', function (Blueprint $table): void {
            $table->decimal('sms_wallet_balance', 10, 2)->default(0.00)->after('allowed_modules');
            $table->decimal('sms_rate_per_message', 6, 4)->default(0.2500)->after('sms_wallet_balance');
            $table->json('sms_settings')->nullable()->after('sms_rate_per_message');
        });

        // 1. DLT Approved Sender IDs / Headers (e.g., FITGNR, AHVSFT)
        Schema::create('sms_sender_ids', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('sender_id', 10)->index();
            $table->string('dlt_entity_id', 50)->nullable()->index();
            $table->string('description')->nullable();
            $table->string('status', 20)->default('active'); // active, pending, disabled
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'sender_id']);
        });

        // 2. DLT Approved Content Templates
        Schema::create('sms_templates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('sms_sender_id')->nullable()->constrained('sms_sender_ids')->nullOnDelete();
            $table->string('name');
            $table->string('dlt_template_id', 50)->index();
            $table->string('template_type', 30)->default('service_implicit'); // transactional, service_implicit, service_explicit, promotional
            $table->text('body');
            $table->json('variables')->nullable();
            $table->string('status', 20)->default('approved'); // approved, pending, rejected
            $table->timestamps();

            $table->unique(['organization_id', 'dlt_template_id']);
        });

        // 3. SMS Campaigns
        Schema::create('sms_campaigns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('sms_sender_id')->nullable()->constrained('sms_sender_ids')->nullOnDelete();
            $table->foreignUlid('sms_template_id')->nullable()->constrained('sms_templates')->nullOnDelete();
            $table->string('name');
            $table->text('message_body');
            $table->unsignedInteger('audience_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->string('status', 20)->default('draft')->index(); // draft, processing, completed, failed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        // 4. SMS Individual Messages / Delivery Logs
        Schema::create('sms_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('sms_campaign_id')->nullable()->constrained('sms_campaigns')->nullOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('to_phone', 20)->index();
            $table->string('sender_id', 10)->index();
            $table->string('dlt_template_id', 50)->nullable()->index();
            $table->text('message_body');
            $table->unsignedTinyInteger('segments')->default(1);
            $table->decimal('cost', 8, 4)->default(0.2500);
            $table->decimal('provider_cost', 8, 4)->default(0.1300);
            $table->string('status', 20)->default('queued')->index(); // queued, sent, delivered, failed
            $table->string('error_message')->nullable();
            $table->string('provider_message_id', 100)->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_campaigns');
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('sms_sender_ids');

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'sms_wallet_balance',
                'sms_rate_per_message',
                'sms_settings',
            ]);
        });
    }
};
