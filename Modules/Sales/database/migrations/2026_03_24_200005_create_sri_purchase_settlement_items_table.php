<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_purchase_settlement_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_settlement_id')->constrained('sales_purchase_settlements')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('inv_products')->nullOnDelete();

            $table->string('product_code', 50)->nullable();
            $table->string('product_name', 300);
            $table->string('product_unit', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('description', 500)->nullable();

            $table->decimal('quantity', 14, 6)->default(1);
            $table->decimal('unit_price', 16, 8)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);

            $table->timestamps();

            $table->index('purchase_settlement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_purchase_settlement_items');
    }
};
