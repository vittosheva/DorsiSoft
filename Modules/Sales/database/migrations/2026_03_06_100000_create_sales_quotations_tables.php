<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Sales\Enums\QuotationStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 30);

            // FK para navegación/filtros — nullable porque el cliente puede ser eliminado
            $table->foreignId('business_partner_id')->nullable()->constrained('core_business_partners')->nullOnDelete();

            // Snapshot de datos del cliente al momento de la cotización
            $table->string('customer_name', 200);
            $table->string('customer_trade_name', 200)->nullable();
            $table->string('customer_identification_type', 20);
            $table->string('customer_identification', 30);
            $table->string('customer_address', 300)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_phone', 30)->nullable();

            // FK vendedor para navegación
            $table->foreignId('seller_id')->nullable()->constrained('core_users')->nullOnDelete();
            $table->string('seller_name', 150)->nullable();

            // FK lista de precios para navegación
            $table->foreignId('price_list_id')->nullable()->constrained('sales_price_lists')->nullOnDelete();
            $table->string('price_list_name', 100)->nullable();

            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 35)->default(QuotationStatusEnum::Draft->value);

            $table->date('issue_date');
            $table->unsignedSmallInteger('validity_days')->default(15);
            $table->date('expires_at');

            $table->text('introduction')->nullable();
            $table->text('notes')->nullable();

            // Descuento global
            $table->string('discount_type', 15)->nullable();
            $table->decimal('discount_value', 10, 4)->nullable();

            // Totales calculados
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_base', 20, 4)->default(0);      // base imponible IVA
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();

            // Referencia a la orden generada (sin FK constrained para evitar dependencia circular)
            $table->unsignedBigInteger('order_id')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'sales_quotations_company_code_unique');
            $table->index(['company_id', 'status', 'deleted_at'], 'sales_quotations_company_status_deleted_idx');
            $table->index(['company_id', 'business_partner_id', 'deleted_at'], 'sales_quotations_company_bp_deleted_idx');
            $table->index(['company_id', 'seller_id', 'deleted_at'], 'sales_quotations_company_seller_deleted_idx');
            $table->index(['company_id', 'issue_date', 'deleted_at'], 'sales_quotations_company_date_deleted_idx');
            $table->index(['company_id', 'expires_at', 'status', 'deleted_at'], 'sales_quotations_company_expires_status_idx');
        });

        Schema::create('sales_quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->constrained('sales_quotations')->cascadeOnDelete();

            // FK para navegación — nullable porque el producto puede ser eliminado
            $table->foreignId('product_id')->nullable()->constrained('inv_products')->nullOnDelete();

            // Snapshot de datos del producto
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

            $table->index(['quotation_id', 'sort_order'], 'sales_qi_quotation_sort_idx');
            $table->index(['quotation_id', 'product_id'], 'sales_qi_quotation_product_idx');
        });

        Schema::create('sales_quotation_item_taxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_item_id')->constrained('sales_quotation_items')->cascadeOnDelete();

            // FK para navegación — nullable porque el impuesto puede ser eliminado
            $table->foreignId('tax_id')->nullable()->constrained('fin_taxes')->nullOnDelete();

            // Snapshot de datos del impuesto
            $table->string('tax_name', 100);
            $table->string('tax_type', 30);
            $table->decimal('tax_rate', 7, 4);

            // Cálculo
            $table->decimal('base_amount', 20, 4);
            $table->decimal('tax_amount', 20, 4);

            $table->timestamps();

            $table->index('quotation_item_id', 'sales_qit_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_quotation_item_taxes');
        Schema::dropIfExists('sales_quotation_items');
        Schema::dropIfExists('sales_quotations');
    }
};
