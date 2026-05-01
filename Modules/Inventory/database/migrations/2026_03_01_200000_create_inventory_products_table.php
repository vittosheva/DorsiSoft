<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('sku', 50)->nullable();
            $table->string('barcode', 50)->nullable();
            $table->enum('barcode_type', ['EAN13', 'UPC', 'CODE128', 'CODE39', 'QR'])->nullable();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('inv_categories')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('inv_brands')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('inv_units')->restrictOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('fin_taxes')->restrictOnDelete();
            $table->string('type', 20)->default('product');
            $table->boolean('is_inventory')->default(true);
            $table->boolean('is_for_sale')->default(true);
            $table->boolean('is_for_purchase')->default(true);
            $table->decimal('standard_cost', 20, 8)->default(0);
            $table->decimal('current_unit_cost', 20, 8)->default(0);
            $table->decimal('sale_price', 20, 8)->default(0);
            $table->decimal('weight', 15, 6)->nullable();
            $table->decimal('volume', 15, 6)->nullable();
            $table->decimal('min_stock', 15, 6)->nullable();
            $table->decimal('max_stock', 15, 6)->nullable();
            $table->decimal('reorder_point', 15, 6)->nullable();
            $table->string('image_url', 512)->nullable();
            $table->string('qr_code_path', 512)->nullable();
            $table->timestamp('qr_generated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('abc_classification', ['A', 'B', 'C', 'X'])->nullable();
            $table->decimal('annual_value', 20, 2)->default(0);
            $table->timestamp('abc_calculated_at')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->index(['company_id', 'category_id', 'is_active', 'deleted_at'], 'inv_products_company_cat_active_index');
            $table->index(['company_id', 'brand_id', 'is_active', 'deleted_at'], 'inv_products_company_brand_active_index');
            $table->index(['company_id', 'type', 'deleted_at'], 'inv_products_company_type_deleted_index');
            $table->index(['company_id', 'is_active', 'deleted_at'], 'inv_products_company_active_deleted_index');
            $table->index(['company_id', 'abc_classification', 'deleted_at'], 'inv_products_company_abc_deleted_index');
            $table->index(['company_id', 'reorder_point', 'deleted_at'], 'inv_products_company_reorder_deleted_index');
            $table->index(['company_id', 'is_for_sale', 'is_active', 'deleted_at'], 'inv_products_company_sale_active_deleted_index');
            $table->index(['company_id', 'is_for_purchase', 'is_active', 'deleted_at'], 'inv_products_company_purchase_active_deleted_index');
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['name'], 'ftidx_products_name');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_products');
    }
};
