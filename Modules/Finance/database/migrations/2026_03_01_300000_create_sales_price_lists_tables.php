<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_price_lists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'sales_price_lists_company_code_unique');
            $table->unique(['company_id', 'name'], 'sales_price_lists_company_name_unique');
            $table->index(['company_id', 'is_active', 'deleted_at'], 'sales_price_lists_company_active_deleted_index');
            $table->index(['company_id', 'start_date', 'end_date', 'deleted_at'], 'sales_price_lists_company_dates_deleted_index');
        });

        Schema::create('sales_price_list_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_list_id')->constrained('sales_price_lists')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('inv_products')->nullOnDelete();
            $table->decimal('price', 20, 8);
            $table->decimal('min_quantity', 15, 6)->default(1);
            $table->timestamps();

            $table->unique(['price_list_id', 'product_id', 'min_quantity'], 'sales_pli_price_list_product_qty_unique');
            $table->index(['price_list_id', 'product_id'], 'sales_pli_price_list_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_price_list_items');
        Schema::dropIfExists('sales_price_lists');
    }
};
