<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('whatsapp_business_account_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('language');                 // e.g. en, en_US
            $table->string('category')->nullable();     // MARKETING | UTILITY | AUTHENTICATION (not enum-locked)
            $table->string('status')->default('UNKNOWN'); // PENDING|APPROVED|REJECTED|PAUSED|DISABLED|UNKNOWN

            $table->string('meta_template_id')->nullable();
            $table->json('components')->nullable();     // normalized builder representation
            $table->json('raw_meta')->nullable();       // last raw Meta payload
            $table->text('rejection_reason')->nullable();
            $table->string('quality_score')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['whatsapp_business_account_id', 'name', 'language'], 'wa_templates_account_name_lang_unique');
            $table->index(['organization_id', 'status'], 'wa_templates_org_status_index');
            $table->index(['organization_id', 'category'], 'wa_templates_org_category_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
