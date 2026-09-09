<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_campaigns', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            // Local copy of the recorded clip; uploaded to the provider on launch.
            $table->foreignUlid('audio_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('provider_library_id')->nullable();

            $table->string('status')->default('draft'); // draft|scheduled|processing|paused|completed|cancelled
            $table->json('audience_filter')->nullable(); // {type, group_ids, contact_ids, exclude_group_ids}
            $table->unsignedSmallInteger('delay_seconds')->default(5); // gap between calls
            $table->timestamp('scheduled_at')->nullable();
            $table->string('timezone')->default('Asia/Kolkata');

            // The sender's explicit confirmation that recipients consented and the
            // campaign complies with TRAI DLT / DND rules. Required before launch.
            $table->boolean('consent_confirmed')->default(false);

            $table->json('audience_summary')->nullable();
            $table->json('totals')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'created_at']);
        });

        Schema::create('voice_call_recipients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('voice_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained()->nullOnDelete();

            $table->string('phone_e164');
            $table->string('phone_hash', 64)->index();

            $table->string('status')->default('pending'); // see App\Enums\VoiceCallStatus
            $table->string('provider_uuid')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('called_at')->nullable();
            $table->timestamps();

            $table->unique(['voice_campaign_id', 'phone_e164']);
            $table->index(['voice_campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_call_recipients');
        Schema::dropIfExists('voice_campaigns');
    }
};
