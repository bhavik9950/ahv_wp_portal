<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('billing_email', 150)->nullable()->after('timezone');
            $table->text('billing_address')->nullable()->after('billing_email');
            $table->string('gst_number', 30)->nullable()->after('billing_address');
            $table->string('meta_billing_mode', 30)->default('client_card')->after('gst_number');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30); // subscription | monthly_usage | custom

            $table->string('client_name', 150);
            $table->string('client_email', 150);
            $table->text('client_address')->nullable();
            $table->string('client_gst', 30)->nullable();

            $table->string('meta_billing_mode', 30)->nullable(); // company_card | client_card
            $table->string('billing_month', 7)->nullable(); // YYYY-MM
            $table->string('plan', 50)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('INR');

            $table->string('status', 20)->default('unpaid'); // unpaid | paid | cancelled
            $table->string('payment_reference', 150)->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_to_email', 150)->nullable();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'type']);
            $table->index(['status', 'issue_date']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['billing_email', 'billing_address', 'gst_number', 'meta_billing_mode']);
        });
    }
};
