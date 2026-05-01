<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_serials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('inv_products')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('inv_warehouses')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inv_lots')->nullOnDelete();
            $table->string('serial_number', 150);
            $table->enum('status', ['available', 'reserved', 'sold', 'returned', 'scrapped'])->default('available');
            $table->timestamp('sold_at')->nullable();
            $table->unsignedBigInteger('sold_movement_id')->nullable();
            $table->timestamps();
            $table->userstamps();

            $table->unique(['company_id', 'product_id', 'serial_number'], 'inv_serials_company_product_serial_unique');
            $table->index(['company_id', 'product_id', 'status'], 'inv_serials_company_product_status_index');
            $table->index(['company_id', 'warehouse_id', 'status'], 'inv_serials_company_warehouse_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_serials');
    }
};
