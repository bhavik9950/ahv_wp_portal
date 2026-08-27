<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_business_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            $table->string('meta_business_account_id')->nullable();
            $table->string('waba_id'); // WhatsApp Business Account ID
            $table->string('app_id')->nullable();

            // Encrypted at rest (see model casts). Never returned by API resources.
            $table->text('access_token')->nullable();
            $table->text('app_secret')->nullable();
            $table->text('webhook_verify_token')->nullable();

            $table->string('api_version')->nullable();          // overrides global default
            $table->string('default_country_code')->nullable();

            $table->timestamp('token_last_checked_at')->nullable();
            $table->string('token_status')->default('unknown');  // unknown|valid|invalid|expired
            $table->timestamp('token_expires_at')->nullable();
            $table->string('connection_status')->default('unknown'); // unknown|connected|error
            $table->json('last_error')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'waba_id']);
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('whatsapp_business_account_id')->constrained()->cascadeOnDelete();

            $table->string('phone_number_id'); // Meta Phone Number ID
            $table->string('display_phone_number')->nullable();
            $table->string('verified_name')->nullable();
            $table->string('quality_rating')->nullable();       // GREEN|YELLOW|RED
            $table->string('messaging_limit_tier')->nullable();
            $table->string('status')->default('unknown');       // unknown|available|error|disabled
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['whatsapp_business_account_id', 'phone_number_id'], 'wa_phone_numbers_account_number_unique');
            $table->index(['organization_id', 'status'], 'wa_phone_numbers_org_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_phone_numbers');
        Schema::dropIfExists('whatsapp_business_accounts');
    }
};
