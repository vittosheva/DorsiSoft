<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align sales_orders and sales_order_items to the standard transactional document schema.
 *
 * Changes:
 * - sales_orders: add metadata, discount_type, discount_value
 * - sales_order_items: add detail_1, detail_2; DROP flat tax columns (tax_name, tax_rate)
 * - Create sales_order_item_taxes (relational, mirrors sales_quotation_item_taxes)
 *
 * Data safety: only test data exists — DROP columns are destructive.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── sales_orders: add missing standard fields ───────────────────────────
        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->string('discount_type', 15)->nullable()->after('notes');
            $table->decimal('discount_value', 10, 4)->nullable()->after('discount_type');
            $table->json('metadata')->nullable()->after('total');
        });

        // ── sales_order_items: align to standard item schema ────────────────────
        Schema::table('sales_order_items', function (Blueprint $table): void {
            $table->string('detail_1', 255)->nullable()->after('description');
            $table->string('detail_2', 255)->nullable()->after('detail_1');
            $table->dropColumn(['tax_name', 'tax_rate']);
        });

        // ── sales_order_item_taxes: new relational tax table ────────────────────
        Schema::create('sales_order_item_taxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained('sales_order_items')->cascadeOnDelete();

            // FK para navegación — nullable porque el impuesto puede ser eliminado
            $table->foreignId('tax_id')->nullable()->constrained('fin_taxes')->nullOnDelete();

            // Snapshot del impuesto al momento del documento
            $table->string('tax_name', 100);
            $table->string('tax_type', 30);
            $table->decimal('tax_rate', 7, 4);

            // Cálculo
            $table->decimal('base_amount', 20, 4);
            $table->decimal('tax_amount', 20, 4);

            $table->timestamps();

            $table->index('order_item_id', 'sales_oit_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_item_taxes');

        Schema::table('sales_order_items', function (Blueprint $table): void {
            $table->dropColumn(['detail_1', 'detail_2']);
            $table->string('tax_name', 100)->nullable()->after('product_unit');
            $table->decimal('tax_rate', 7, 4)->default(0)->after('tax_name');
        });

        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->dropColumn(['discount_type', 'discount_value', 'metadata']);
        });
    }
};
