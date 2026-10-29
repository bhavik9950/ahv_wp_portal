<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('plan')->default('monthly')->after('status'); // trial | monthly | half_yearly | yearly | lifetime | custom
            $table->timestamp('subscription_expires_at')->nullable()->after('plan');
            $table->boolean('is_lifetime')->default(false)->after('subscription_expires_at');
            $table->json('allowed_modules')->nullable()->after('is_lifetime'); // null = all modules allowed
        });

        Schema::create('subscription_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan'); // monthly | half_yearly | yearly | lifetime
            $table->unsignedInteger('amount')->default(0);
            $table->string('utr_number')->index();
            $table->string('payment_method')->default('upi'); // upi | bank_transfer
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->text('notes')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_requests');

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'plan',
                'subscription_expires_at',
                'is_lifetime',
                'allowed_modules',
            ]);
        });
    }
};

