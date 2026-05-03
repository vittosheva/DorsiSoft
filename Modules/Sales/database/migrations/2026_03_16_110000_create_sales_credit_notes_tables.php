<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Sales\Enums\CreditNoteStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 30);

            // Factura original que se acredita (1 NC = 1 Factura, SRI Ecuador)
            $table->foreignId('invoice_id')->constrained('sales_invoices')->cascadeOnDelete();

            $table->date('issue_date');

            // Pago y reversión que originaron la NC (nullable: NC puede ser standalone)
            $table->foreignId('payment_id')->nullable()->constrained('sales_payments')->nullOnDelete();
            $table->foreignId('payment_allocation_reversal_id')
                ->nullable()
                ->constrained('sales_payment_allocation_reversals')
                ->nullOnDelete();

            // Cliente
            $table->foreignId('business_partner_id')->nullable()->constrained('core_business_partners')->nullOnDelete();
            $table->string('customer_name', 200)->nullable();
            $table->string('customer_trade_name', 200)->nullable();
            $table->string('customer_identification_type', 50)->nullable();
            $table->string('customer_identification', 20)->nullable();
            $table->text('customer_address')->nullable();
            $table->json('customer_email')->nullable();
            $table->string('customer_phone', 30)->nullable();

            // Montos
            $table->char('currency_code', 3)->default('USD');
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);
            $table->decimal('applied_amount', 20, 4)->default(0);
            $table->decimal('refunded_amount', 20, 4)->default(0);

            // Estado y metadatos
            $table->string('status', 35)->default(CreditNoteStatusEnum::Draft->value);
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Timestamps de ciclo de vida
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('voided_reason')->nullable();

            // Audit
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'credit_notes_company_code_unique');
            $table->index(['company_id', 'status', 'deleted_at'], 'credit_notes_company_status_deleted_idx');
            $table->index(['company_id', 'invoice_id', 'deleted_at'], 'credit_notes_company_invoice_deleted_idx');
            $table->index(['company_id', 'business_partner_id', 'deleted_at'], 'credit_notes_company_bp_deleted_idx');
            $table->index(['company_id', 'issued_at', 'deleted_at'], 'credit_notes_company_issued_deleted_idx');
        });

        Schema::create('sales_credit_note_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('credit_note_id')->constrained('sales_credit_notes')->cascadeOnDelete();

            // Snapshot del producto
            $table->foreignId('product_id')->nullable()->constrained('inv_products')->nullOnDelete();
            $table->string('product_code', 50)->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('product_unit', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();

            // Cantidades y precios
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 8);
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 20, 4)->nullable();
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('subtotal', 20, 4);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total', 20, 4);

            $table->timestamps();

            $table->index('credit_note_id', 'credit_note_items_cn_idx');
        });

        Schema::create('sales_credit_note_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('credit_note_id')->constrained('sales_credit_notes')->cascadeOnDelete();

            // Factura DESTINO (distinta de la factura original de la NC)
            $table->foreignId('invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->decimal('amount', 20, 4);
            $table->timestamp('applied_at')->useCurrent();

            $table->timestamps();

            $table->unique(['credit_note_id', 'invoice_id'], 'cn_applications_cn_invoice_unique');
            $table->index('invoice_id', 'cn_applications_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_credit_note_applications');
        Schema::dropIfExists('sales_credit_note_items');
        Schema::dropIfExists('sales_credit_notes');
    }
};
