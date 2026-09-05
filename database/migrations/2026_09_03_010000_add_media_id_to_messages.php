<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Set once an inbound image/video/audio/document is downloaded from
            // Meta and stored locally — lets the conversation view render it.
            $table->foreignUlid('media_id')->nullable()->after('payload')
                ->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
        });
    }
};
