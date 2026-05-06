<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_sale_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 30);
            $table->foreignId('business_partner_id')->nullable()->constrained('core_business_partners')->nullOnDelete();
            $table->string('customer_name', 200);
            $table->string('customer_trade_name', 200)->nullable();
            $table->string('customer_identification_type', 20)->nullable();
            $table->string('customer_identification', 30)->nullable();
            $table->string('customer_address', 300)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->foreignId('seller_id')->nullable()->constrained('core_users')->nullOnDelete();
            $table->string('seller_name', 150)->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 35)->default('draft');
            $table->date('issue_date');
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_base', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_reason', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('converted_to_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->userstamps();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('sales_sale_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_note_id')->constrained('sales_sale_notes')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('inv_products')->nullOnDelete();
            $table->string('product_code', 50);
            $table->string('product_name', 300);
            $table->string('product_unit', 50)->nullable();
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
        });

        Schema::create('sales_sale_note_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_note_item_id')->constrained('sales_sale_note_items')->cascadeOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('fin_taxes')->nullOnDelete();
            $table->string('tax_name', 100);
            $table->string('tax_type', 30);
            $table->string('tax_code', 30)->nullable();
            $table->string('tax_percentage_code', 30)->nullable();
            $table->string('tax_calculation_type', 30)->default('percent');
            $table->decimal('tax_rate', 7, 4);
            $table->decimal('base_amount', 20, 4);
            $table->decimal('tax_amount', 20, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_sale_note_item_taxes');
        Schema::dropIfExists('sales_sale_note_items');
        Schema::dropIfExists('sales_sale_notes');
    }
};
