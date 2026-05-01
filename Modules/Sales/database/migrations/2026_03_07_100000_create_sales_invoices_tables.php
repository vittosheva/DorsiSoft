<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 30);

            // FK para navegación — nullable porque la orden puede ser eliminada
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('business_partner_id')->nullable()->constrained('core_business_partners')->nullOnDelete();

            // Snapshot del cliente al momento de la factura
            $table->string('customer_name', 200);
            $table->string('customer_trade_name', 200)->nullable();
            $table->string('customer_identification_type', 20);
            $table->string('customer_identification', 30);
            $table->string('customer_address', 300)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_phone', 30)->nullable();

            $table->foreignId('seller_id')->nullable()->constrained('core_users')->nullOnDelete();
            $table->string('seller_name', 150)->nullable();

            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 20)->default('draft');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();

            // Totales calculados
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_base', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);

            // Ciclo de vida del documento
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_reason', 500)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'sales_invoices_company_code_unique');
            $table->index(['company_id', 'status', 'deleted_at'], 'sales_invoices_company_status_deleted_idx');
            $table->index(['company_id', 'business_partner_id', 'deleted_at'], 'sales_invoices_company_bp_deleted_idx');
            $table->index(['company_id', 'issue_date', 'deleted_at'], 'sales_invoices_company_issue_date_deleted_idx');
        });

        // Add FK from sales_orders.invoice_id → sales_invoices now that the table exists
        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('invoice_id')->nullable()->after('quotation_id');
            $table->foreign('invoice_id', 'sales_orders_invoice_id_foreign')
                ->references('id')
                ->on('sales_invoices')
                ->nullOnDelete();
        });

        Schema::create('sales_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('sales_invoices')->cascadeOnDelete();

            // FK para navegación — nullable porque el producto puede ser eliminado
            $table->foreignId('product_id')->nullable()->constrained('inv_products')->nullOnDelete();

            // Snapshot del producto
            $table->string('product_code', 50);
            $table->string('product_name', 300);
            $table->string('product_unit', 50)->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('description', 500)->nullable();
            $table->string('detail_1', 255)->nullable();
            $table->string('detail_2', 255)->nullable();

            $table->decimal('quantity', 15, 6);
            $table->decimal('unit_price', 20, 8);

            // Descuento por línea
            $table->string('discount_type', 15)->nullable();
            $table->decimal('discount_value', 10, 4)->nullable();
            $table->decimal('discount_amount', 20, 4)->default(0);

            // Totales calculados
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);

            $table->timestamps();

            $table->index(['invoice_id', 'sort_order'], 'sales_ii_invoice_sort_idx');
            $table->index(['invoice_id', 'product_id'], 'sales_ii_invoice_product_idx');
        });

        Schema::create('sales_invoice_item_taxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_item_id')->constrained('sales_invoice_items')->cascadeOnDelete();

            // FK para navegación — nullable porque el impuesto puede ser eliminado
            $table->foreignId('tax_id')->nullable()->constrained('fin_taxes')->nullOnDelete();

            // Snapshot del impuesto
            $table->string('tax_name', 100);
            $table->string('tax_type', 30);
            $table->decimal('tax_rate', 7, 4);

            // Cálculo
            $table->decimal('base_amount', 20, 4);
            $table->decimal('tax_amount', 20, 4);

            $table->timestamps();

            $table->index('invoice_item_id', 'sales_iit_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_item_taxes');
        Schema::dropIfExists('sales_invoice_items');

        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->dropForeign('sales_orders_invoice_id_foreign');
            $table->dropColumn('invoice_id');
        });

        Schema::dropIfExists('sales_invoices');
    }
};
