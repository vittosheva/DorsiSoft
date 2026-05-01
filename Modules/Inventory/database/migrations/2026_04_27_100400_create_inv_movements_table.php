<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('inv_warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('inv_products')->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained('inv_document_types')->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inv_lots')->restrictOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('inv_serials')->restrictOnDelete();
            // Polymorphic source document
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            // Quantities — always positive; direction determined by document_type.movement_type
            $table->decimal('quantity', 14, 6);
            $table->decimal('unit_cost', 20, 8)->default(0);
            // Reference
            $table->string('reference_code', 100)->nullable();
            $table->text('notes')->nullable();
            // Immutability: voiding creates a reversal movement
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->unsignedBigInteger('reversal_movement_id')->nullable();
            $table->boolean('is_reversal')->default(false);
            // Business date (may differ from created_at for backdate adjustments)
            $table->date('movement_date');
            $table->timestamps();
            $table->userstamps();

            $table->index(['company_id', 'product_id', 'warehouse_id', 'movement_date'], 'inv_movements_stock_calc_index');
            $table->index(['company_id', 'warehouse_id', 'voided_at'], 'inv_movements_company_wh_voided_index');
            $table->index(['source_type', 'source_id'], 'inv_movements_source_index');
            $table->index(['company_id', 'product_id', 'lot_id'], 'inv_movements_company_product_lot_index');
            $table->index(['reversal_movement_id'], 'inv_movements_reversal_index');
            $table->index(['company_id', 'document_type_id', 'movement_date'], 'inv_movements_company_doctype_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_movements');
    }
};
