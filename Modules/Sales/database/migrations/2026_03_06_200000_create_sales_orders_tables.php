<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Sales\Enums\SalesOrderStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 30);

            $table->foreignId('quotation_id')->nullable()->constrained('sales_quotations')->nullOnDelete();
            $table->foreignId('business_partner_id')->nullable()->constrained('core_business_partners')->nullOnDelete();

            // Snapshot del cliente
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
            $table->string('status', 35)->default(SalesOrderStatusEnum::Pending->value);
            $table->date('issue_date');
            $table->text('notes')->nullable();

            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_base', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);

            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'sales_orders_company_code_unique');
            $table->index(['company_id', 'status', 'deleted_at'], 'sales_orders_company_status_deleted_idx');
            $table->index(['company_id', 'business_partner_id', 'deleted_at'], 'sales_orders_company_bp_deleted_idx');
            $table->index(['company_id', 'issue_date', 'deleted_at'], 'sales_orders_company_issue_date_deleted_idx');
        });

        // Add FK from sales_quotations.order_id → sales_orders now that the table exists
        Schema::table('sales_quotations', function (Blueprint $table): void {
            $table->foreign('order_id', 'sales_quotations_order_id_foreign')
                ->references('id')
                ->on('sales_orders')
                ->nullOnDelete();
        });

        Schema::create('sales_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('sales_orders')->cascadeOnDelete();

            $table->foreignId('product_id')->nullable()->constrained('inv_products')->nullOnDelete();

            // Snapshot del producto
            $table->string('product_code', 50);
            $table->string('product_name', 300);
            $table->string('product_unit', 50)->nullable();

            $table->string('tax_name', 100)->nullable();
            $table->decimal('tax_rate', 7, 4)->default(0);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 15, 6);
            $table->decimal('unit_price', 20, 8);

            $table->string('discount_type', 15)->nullable();
            $table->decimal('discount_value', 10, 4)->nullable();
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);

            $table->timestamps();

            $table->index(['order_id', 'sort_order'], 'sales_oi_order_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');

        Schema::table('sales_quotations', function (Blueprint $table): void {
            $table->dropForeign('sales_quotations_order_id_foreign');
        });

        Schema::dropIfExists('sales_orders');
    }
};
