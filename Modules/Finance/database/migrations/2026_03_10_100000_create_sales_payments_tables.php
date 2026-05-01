<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add paid_amount tracking to existing invoices table
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->decimal('paid_amount', 20, 4)->default(0)->after('total');
        });

        // Payments master table
        Schema::create('sales_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 30);

            $table->foreignId('business_partner_id')->nullable()->constrained('core_business_partners')->nullOnDelete();

            // Snapshot del cliente al momento del pago
            $table->string('customer_name', 200)->nullable();

            $table->date('payment_date');
            $table->decimal('amount', 20, 4);
            $table->char('currency_code', 3)->default('USD');
            $table->string('payment_method', 30);
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();

            // Voiding
            $table->timestamp('voided_at')->nullable();
            $table->text('voided_reason')->nullable();

            // Audit
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'sales_payments_company_code_unique');
            $table->index(['company_id', 'payment_date', 'deleted_at'], 'payments_company_date_deleted_idx');
            $table->index(['company_id', 'business_partner_id', 'deleted_at'], 'payments_company_bp_deleted_idx');
            $table->index(['company_id', 'payment_method', 'deleted_at'], 'payments_company_method_deleted_idx');
        });

        // Junction table: one payment can cover multiple invoices
        Schema::create('sales_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('sales_payments')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->decimal('amount', 20, 4);
            $table->timestamp('allocated_at')->useCurrent();
            $table->timestamps();

            $table->unique(['payment_id', 'invoice_id'], 'allocations_payment_invoice_unique');
            $table->index('invoice_id', 'allocations_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_payment_allocations');
        Schema::dropIfExists('sales_payments');

        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->dropColumn('paid_amount');
        });
    }
};
