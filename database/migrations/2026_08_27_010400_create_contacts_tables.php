<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name')->nullable();
            $table->string('country_code')->nullable();
            $table->string('phone_e164');
            $table->string('phone_hash', 64)->index();
            $table->string('email')->nullable();
            $table->json('custom_fields')->nullable();

            $table->string('opt_in_status')->default('unknown'); // unknown|opted_in|opted_out
            $table->timestamp('opted_in_at')->nullable();
            $table->string('opt_in_source')->nullable();
            $table->timestamp('opted_out_at')->nullable();

            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'phone_e164']);
            $table->index(['organization_id', 'opt_in_status']);
        });

        Schema::create('contact_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('contact_group_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('contact_group_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contact_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['contact_group_id', 'contact_id']);
        });

        // Append-only consent ledger.
        Schema::create('opt_in_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone_e164')->index();
            $table->string('status'); // opt_in | opt_out
            $table->string('source')->nullable();
            $table->ulid('campaign_id')->nullable();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'phone_e164', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opt_in_records');
        Schema::dropIfExists('contact_group_contact');
        Schema::dropIfExists('contact_groups');
        Schema::dropIfExists('contacts');
    }
};
