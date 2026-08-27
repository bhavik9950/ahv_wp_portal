<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('audience_summary')->nullable()->after('audience_filter');
            $table->timestamp('confirmed_at')->nullable()->after('scheduled_at');
            $table->foreignId('paused_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->string('error_code')->nullable()->after('skip_reason');
            $table->text('error_message')->nullable()->after('error_code');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paused_by');
            $table->dropColumn(['audience_summary', 'confirmed_at']);
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropColumn(['error_code', 'error_message']);
        });
    }
};
