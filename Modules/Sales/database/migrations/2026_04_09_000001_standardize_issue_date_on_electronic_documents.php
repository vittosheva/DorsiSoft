<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {

        // ─── Add partial indexes for active-only queries ─────────────────

        DB::statement('CREATE INDEX sales_invoices_active_issue_date_idx ON sales_invoices (company_id, issue_date) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX sales_credit_notes_active_issue_date_idx ON sales_credit_notes (company_id, issue_date) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX sales_debit_notes_active_issue_date_idx ON sales_debit_notes (company_id, issue_date) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX sales_withholdings_active_issue_date_idx ON sales_withholdings (company_id, issue_date) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX sales_purchase_settlements_active_issue_date_idx ON sales_purchase_settlements (company_id, issue_date) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX sales_delivery_guides_active_issue_date_idx ON sales_delivery_guides (company_id, issue_date) WHERE deleted_at IS NULL');

        // ─── Recreate reports view with issue_date ───────────────────────

        DB::statement('DROP VIEW IF EXISTS v_rpt_monthly_sales');
        DB::statement("
            CREATE OR REPLACE VIEW v_rpt_monthly_sales AS
            SELECT
                company_id,
                seller_id,
                seller_name,
                EXTRACT(YEAR FROM issue_date)::int AS year,
                EXTRACT(MONTH FROM issue_date)::int AS month,
                COUNT(*) AS invoice_count,
                SUM(total) AS revenue,
                SUM(paid_amount) AS collected,
                SUM(total - paid_amount - credited_amount) AS pending,
                SUM(tax_amount) AS tax_total,
                SUM(discount_amount) AS discount_total
            FROM sales_invoices
            WHERE status IN ('issued', 'partial', 'paid')
              AND deleted_at IS NULL
            GROUP BY company_id, seller_id, seller_name, EXTRACT(YEAR FROM issue_date), EXTRACT(MONTH FROM issue_date)
        ");
    }

    public function down(): void
    {
        // Restore original view
        DB::statement('DROP VIEW IF EXISTS v_rpt_monthly_sales');
        DB::statement("
            CREATE OR REPLACE VIEW v_rpt_monthly_sales AS
            SELECT
                company_id,
                seller_id,
                seller_name,
                EXTRACT(YEAR FROM issued_at)::int AS year,
                EXTRACT(MONTH FROM issued_at)::int AS month,
                COUNT(*) AS invoice_count,
                SUM(total) AS revenue,
                SUM(paid_amount) AS collected,
                SUM(total - paid_amount - credited_amount) AS pending,
                SUM(tax_amount) AS tax_total,
                SUM(discount_amount) AS discount_total
            FROM sales_invoices
            WHERE status IN ('issued', 'partial', 'paid')
              AND deleted_at IS NULL
            GROUP BY company_id, seller_id, seller_name, EXTRACT(YEAR FROM issued_at), EXTRACT(MONTH FROM issued_at)
        ");

        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS sales_invoices_active_issue_date_idx');
        DB::statement('DROP INDEX IF EXISTS sales_credit_notes_active_issue_date_idx');
        DB::statement('DROP INDEX IF EXISTS sales_debit_notes_active_issue_date_idx');
        DB::statement('DROP INDEX IF EXISTS sales_withholdings_active_issue_date_idx');
        DB::statement('DROP INDEX IF EXISTS sales_purchase_settlements_active_issue_date_idx');
        DB::statement('DROP INDEX IF EXISTS sales_delivery_guides_active_issue_date_idx');
    }
};
