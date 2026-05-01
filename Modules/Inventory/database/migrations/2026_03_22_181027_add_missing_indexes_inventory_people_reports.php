<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── inv_categories ───────────────────────────────────────────────────
        Schema::table('inv_categories', function (Blueprint $table): void {
            $table->unique(['company_id', 'code'], 'inv_categories_company_code_unique');
        });

        // ─── inv_brands ───────────────────────────────────────────────────────
        Schema::table('inv_brands', function (Blueprint $table): void {
            $table->unique(['company_id', 'name'], 'inv_brands_company_name_unique');
        });

        // ─── inv_units ────────────────────────────────────────────────────────
        Schema::table('inv_units', function (Blueprint $table): void {
            $table->unique(['company_id', 'code'], 'inv_units_company_code_unique');
        });

        // ─── inv_products ─────────────────────────────────────────────────────
        // PRECONDITION: verify no duplicate codes exist before running:
        //   SELECT company_id, code, COUNT(*) FROM inv_products GROUP BY company_id, code HAVING COUNT(*) > 1
        Schema::table('inv_products', function (Blueprint $table): void {
            $table->unique(['company_id', 'code'], 'inv_products_company_code_unique');
            $table->index(['company_id', 'barcode'], 'inv_products_company_barcode_idx');
        });

        // Partial unique index for SKU (NULL allowed — only unique when present)
        DB::statement('CREATE UNIQUE INDEX inv_products_company_sku_unique ON inv_products (company_id, sku) WHERE sku IS NOT NULL AND deleted_at IS NULL');

        // ─── core_customer_details ────────────────────────────────────────────
        // seller_id is used in seller performance reports and pre-fill logic
        Schema::table('core_customer_details', function (Blueprint $table): void {
            $table->index('seller_id', 'core_customer_details_seller_id_idx');
        });

        // ─── core_partner_addresses ───────────────────────────────────────────
        // Replace the existing default-lookup index with a partial index.
        // PostgreSQL partial index (WHERE deleted_at IS NULL) is smaller and faster.
        Schema::table('core_partner_addresses', function (Blueprint $table): void {
            $table->dropIndex('idx_partner_addresses_bp_default');
        });

        DB::statement('CREATE INDEX idx_partner_addresses_bp_default ON core_partner_addresses (business_partner_id, is_default, is_active) WHERE deleted_at IS NULL');

        // ─── rpt_executions ───────────────────────────────────────────────────
        Schema::table('rpt_executions', function (Blueprint $table): void {
            $table->index('saved_report_id', 'rpt_executions_saved_report_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inv_categories', function (Blueprint $table): void {
            $table->dropUnique('inv_categories_company_code_unique');
        });

        Schema::table('inv_brands', function (Blueprint $table): void {
            $table->dropUnique('inv_brands_company_name_unique');
        });

        Schema::table('inv_units', function (Blueprint $table): void {
            $table->dropUnique('inv_units_company_code_unique');
        });

        Schema::table('inv_products', function (Blueprint $table): void {
            $table->dropUnique('inv_products_company_code_unique');
            $table->dropIndex('inv_products_company_barcode_idx');
        });

        DB::statement('DROP INDEX IF EXISTS inv_products_company_sku_unique');

        Schema::table('core_customer_details', function (Blueprint $table): void {
            $table->dropIndex('core_customer_details_seller_id_idx');
        });

        DB::statement('DROP INDEX IF EXISTS idx_partner_addresses_bp_default');
        Schema::table('core_partner_addresses', function (Blueprint $table): void {
            $table->index(
                ['business_partner_id', 'is_default', 'is_active'],
                'idx_partner_addresses_bp_default'
            );
        });

        Schema::table('rpt_executions', function (Blueprint $table): void {
            $table->dropIndex('rpt_executions_saved_report_id_idx');
        });
    }
};
