<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            // Local copy of the sample file uploaded when a media-header template
            // is created — used for the preview and as the header media at send time.
            $table->foreignUlid('header_sample_media_id')->nullable()->after('components')
                ->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('header_sample_media_id');
        });
    }
};
