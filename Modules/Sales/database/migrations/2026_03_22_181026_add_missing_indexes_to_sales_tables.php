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
        // ─── sales_invoices ───────────────────────────────────────────────────
        Schema::table('sales_invoices', function (Blueprint $table): void {
            // C1: AR aging — active invoices by due date + status (ArAgingPage, v_rpt_ar_aging)
            $table->index(['company_id', 'due_date', 'status'], 'sales_invoices_company_due_status_idx');

            // C2: Seller performance reports (SellerPerformancePage, SellerBookPage)
            $table->index(['company_id', 'seller_id', 'issue_date'], 'sales_invoices_company_seller_issue_date_idx');

            // C3: SRI authorized document listing (filtered by issued_at range)
            $table->index(['company_id', 'issued_at'], 'sales_invoices_company_issued_idx');

            // C4: FK traversal — SalesOrder → Invoice relation manager
            $table->index('sales_order_id', 'sales_invoices_sales_order_id_idx');
        });

        // PostgreSQL partial indexes for active-only queries (3-5x smaller, faster)
        DB::statement('CREATE INDEX sales_invoices_active_due_idx ON sales_invoices (company_id, due_date, status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX sales_invoices_active_seller_issue_date_idx ON sales_invoices (company_id, seller_id, issue_date) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX sales_invoices_active_issued_idx ON sales_invoices (company_id, issued_at) WHERE deleted_at IS NULL');

        // ─── sales_orders ─────────────────────────────────────────────────────
        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->index('quotation_id', 'sales_orders_quotation_id_idx');
            $table->index(['company_id', 'seller_id'], 'sales_orders_company_seller_idx');
            $table->index('invoice_id', 'sales_orders_invoice_id_idx');
        });

        // ─── sales_order_items ────────────────────────────────────────────────
        Schema::table('sales_order_items', function (Blueprint $table): void {
            $table->index(['order_id', 'product_id'], 'sales_oi_order_product_idx');
        });

        // ─── sales_quotations ─────────────────────────────────────────────────
        Schema::table('sales_quotations', function (Blueprint $table): void {
            $table->index('price_list_id', 'sales_quotations_price_list_id_idx');
            $table->index('order_id', 'sales_quotations_order_id_idx');
        });

        // ─── sales_credit_notes ───────────────────────────────────────────────
        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->index(['company_id', 'payment_id'], 'credit_notes_company_payment_idx');
            $table->index('payment_allocation_reversal_id', 'credit_notes_par_id_idx');
        });

        // ─── sales_credit_note_items ──────────────────────────────────────────
        // Replace bare FK index with composites.
        // PostgreSQL FK enforcement does NOT require an index on the referencing side.
        Schema::table('sales_credit_note_items', function (Blueprint $table): void {
            $table->dropIndex('credit_note_items_cn_idx');
            $table->index(['credit_note_id', 'sort_order'], 'sales_cni_cn_sort_idx');
            $table->index(['credit_note_id', 'product_id'], 'sales_cni_cn_product_idx');
        });

        // ─── sales_credit_note_item_taxes ─────────────────────────────────────
        Schema::table('sales_credit_note_item_taxes', function (Blueprint $table): void {
            $table->index('credit_note_item_id', 'sales_cnit_item_idx');
        });

        // ─── sales_debit_notes ────────────────────────────────────────────────
        Schema::table('sales_debit_notes', function (Blueprint $table): void {
            $table->index(['company_id', 'invoice_id'], 'sales_debit_notes_company_invoice_idx');
            $table->index('tax_id', 'sales_debit_notes_tax_id_idx');
            $table->index(['company_id', 'issued_at'], 'sales_debit_notes_company_issued_idx');
        });

        // ─── sales_document_sequence_history ──────────────────────────────────
        Schema::table('sales_document_sequence_history', function (Blueprint $table): void {
            $table->index('document_sequence_id', 'sales_seq_history_sequence_id_idx');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS sales_invoices_active_due_idx');
        DB::statement('DROP INDEX IF EXISTS sales_invoices_active_seller_issue_date_idx');
        DB::statement('DROP INDEX IF EXISTS sales_invoices_active_issued_idx');

        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->dropIndex('sales_invoices_company_due_status_idx');
            $table->dropIndex('sales_invoices_company_seller_issue_date_idx');
            $table->dropIndex('sales_invoices_company_issued_idx');
            $table->dropIndex('sales_invoices_sales_order_id_idx');
        });

        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->dropIndex('sales_orders_quotation_id_idx');
            $table->dropIndex('sales_orders_company_seller_idx');
            $table->dropIndex('sales_orders_invoice_id_idx');
        });

        Schema::table('sales_order_items', function (Blueprint $table): void {
            $table->dropIndex('sales_oi_order_product_idx');
        });

        Schema::table('sales_quotations', function (Blueprint $table): void {
            $table->dropIndex('sales_quotations_price_list_id_idx');
            $table->dropIndex('sales_quotations_order_id_idx');
        });

        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->dropIndex('credit_notes_company_payment_idx');
            $table->dropIndex('credit_notes_par_id_idx');
        });

        Schema::table('sales_credit_note_items', function (Blueprint $table): void {
            $table->dropIndex('sales_cni_cn_sort_idx');
            $table->dropIndex('sales_cni_cn_product_idx');
            $table->index('credit_note_id', 'credit_note_items_cn_idx');
        });

        Schema::table('sales_credit_note_item_taxes', function (Blueprint $table): void {
            $table->dropIndex('sales_cnit_item_idx');
        });

        Schema::table('sales_debit_notes', function (Blueprint $table): void {
            $table->dropIndex('sales_debit_notes_company_invoice_idx');
            $table->dropIndex('sales_debit_notes_tax_id_idx');
            $table->dropIndex('sales_debit_notes_company_issued_idx');
        });

        Schema::table('sales_document_sequence_history', function (Blueprint $table): void {
            $table->dropIndex('sales_seq_history_sequence_id_idx');
        });
    }
};
