<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('inv_products')->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inv_lots')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('inv_serials')->nullOnDelete();
            $table->decimal('quantity', 14, 6);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'fulfilled'])->default('pending');
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->userstamps();

            $table->index(['company_id', 'product_id', 'warehouse_id', 'status'], 'inv_reservations_stock_index');
            $table->index(['source_type', 'source_id'], 'inv_reservations_source_index');
            $table->index(['company_id', 'status', 'expires_at'], 'inv_reservations_expiry_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_reservations');
    }
};
