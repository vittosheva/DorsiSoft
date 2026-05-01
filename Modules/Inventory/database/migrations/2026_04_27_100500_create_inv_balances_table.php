<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('inv_products')->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inv_lots')->cascadeOnDelete();
            $table->decimal('quantity_available', 14, 6)->default(0);
            $table->decimal('quantity_reserved', 14, 6)->default(0);
            $table->decimal('average_cost', 20, 8)->default(0);
            $table->unsignedBigInteger('last_movement_id')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(
                ['company_id', 'warehouse_id', 'product_id', 'lot_id'],
                'inv_balances_unique_dimension'
            );
            $table->index(['company_id', 'warehouse_id', 'product_id'], 'inv_balances_lookup_index');
            $table->index(['company_id', 'product_id'], 'inv_balances_company_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_balances');
    }
};
