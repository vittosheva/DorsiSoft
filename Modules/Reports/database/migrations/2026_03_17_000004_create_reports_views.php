<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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

        DB::statement("
            CREATE OR REPLACE VIEW v_rpt_ar_aging AS
            SELECT
                company_id,
                business_partner_id,
                customer_name,
                COUNT(*) AS invoice_count,
                SUM(total - paid_amount - credited_amount) AS total_pending,
                SUM(CASE WHEN (NOW()::date - due_date) <= 0 THEN total - paid_amount - credited_amount ELSE 0 END) AS band_current,
                SUM(CASE WHEN (NOW()::date - due_date) BETWEEN 1 AND 30 THEN total - paid_amount - credited_amount ELSE 0 END) AS band_30,
                SUM(CASE WHEN (NOW()::date - due_date) BETWEEN 31 AND 60 THEN total - paid_amount - credited_amount ELSE 0 END) AS band_60,
                SUM(CASE WHEN (NOW()::date - due_date) BETWEEN 61 AND 90 THEN total - paid_amount - credited_amount ELSE 0 END) AS band_90,
                SUM(CASE WHEN (NOW()::date - due_date) > 90 THEN total - paid_amount - credited_amount ELSE 0 END) AS band_90plus
            FROM sales_invoices
            WHERE status IN ('issued', 'partial')
              AND deleted_at IS NULL
            GROUP BY company_id, business_partner_id, customer_name
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_rpt_monthly_sales');
        DB::statement('DROP VIEW IF EXISTS v_rpt_ar_aging');
    }
};
