<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();

            $table->string('source')->default('meta');
            $table->string('event_fingerprint', 64)->unique();
            $table->boolean('signature_valid')->default(false);
            $table->json('payload');
            $table->json('headers')->nullable();       // minus Authorization

            $table->string('status')->default('received'); // received|processing|processed|failed|ignored
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('received_at')->useCurrent();

            $table->index(['status', 'received_at']);
            $table->index(['organization_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
