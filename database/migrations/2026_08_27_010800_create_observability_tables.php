<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('request_id')->nullable()->index();
            $table->string('service');            // e.g. whatsapp
            $table->string('operation');          // sendTemplate, fetchTemplates, ...
            $table->string('endpoint')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error_category')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('context')->nullable();  // ids only, never secrets
            $table->timestamp('created_at')->useCurrent();

            $table->index(['service', 'operation', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('action')->index();    // login, waba.updated, template.submitted, ...
            $table->string('auditable_type')->nullable();
            $table->string('auditable_id')->nullable();
            $table->string('result')->default('success'); // success | failure
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // no secrets
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('api_logs');
    }
};
