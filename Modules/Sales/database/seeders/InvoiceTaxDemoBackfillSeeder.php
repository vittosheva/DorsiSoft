<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceItemTax;
use Modules\Sales\Services\InvoiceTotalsCalculator;

final class InvoiceTaxDemoBackfillSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function __construct(private readonly InvoiceTotalsCalculator $calculator) {}

    /**
     * Repair seeded demo invoices created before invoice taxes became deterministic.
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command?->warn('InvoiceTaxDemoBackfillSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;
        $demoCodes = $this->demoInvoiceCodes();

        /** @var Collection<int, int> $invoiceIds */
        $invoiceIds = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereIn('status', [
                InvoiceStatusEnum::Draft->value,
                InvoiceStatusEnum::Issued->value,
                InvoiceStatusEnum::Paid->value,
            ])
            ->whereIn('code', $demoCodes)
            ->orderBy('id')
            ->pluck('id');

        if ($invoiceIds->isEmpty()) {
            $this->reportSkipped('demo invoices not found');

            return;
        }

        $items = DB::table('sales_invoice_items')
            ->leftJoin('inv_products', 'sales_invoice_items.product_id', '=', 'inv_products.id')
            ->leftJoin('fin_taxes', 'inv_products.tax_id', '=', 'fin_taxes.id')
            ->whereIn('sales_invoice_items.invoice_id', $invoiceIds)
            ->orderBy('sales_invoice_items.invoice_id')
            ->orderBy('sales_invoice_items.id')
            ->get([
                'sales_invoice_items.id',
                'sales_invoice_items.invoice_id',
                'fin_taxes.id as tax_id',
                'fin_taxes.name as tax_name',
                'fin_taxes.type as tax_type',
                'fin_taxes.sri_code as tax_code',
                'fin_taxes.sri_percentage_code as tax_percentage_code',
                'fin_taxes.rate as tax_rate',
                'fin_taxes.calculation_type as tax_calculation_type',
            ]);

        if ($items->isEmpty()) {
            $this->reportSkipped('0 invoice items synchronized');

            return;
        }

        foreach ($items as $item) {
            DB::table('sales_invoice_item_taxes')
                ->where('invoice_item_id', $item->id)
                ->delete();

            if (! $item->tax_id) {
                continue;
            }

            InvoiceItemTax::create([
                'invoice_item_id' => $item->id,
                'tax_id' => $item->tax_id,
                'tax_name' => $item->tax_name,
                'tax_type' => $item->tax_type,
                'tax_code' => $item->tax_code,
                'tax_percentage_code' => $item->tax_percentage_code,
                'tax_rate' => $item->tax_rate,
                'tax_calculation_type' => $item->tax_calculation_type,
                'base_amount' => 0,
                'tax_amount' => 0,
            ]);
        }

        foreach ($items->pluck('invoice_id')->unique()->values() as $invoiceId) {
            $invoice = Invoice::query()
                ->withoutGlobalScopes()
                ->with(['items.taxes'])
                ->find($invoiceId);

            if (! $invoice) {
                continue;
            }

            $this->calculator->recalculate($invoice);
        }

        $this->reportSynchronized($items->count(), 'invoice item');
    }

    /**
     * @return list<string>
     */
    private function demoInvoiceCodes(): array
    {
        $year = now()->year;

        return collect(range(1, 15))
            ->map(fn (int $sequence): string => sprintf('FAC-%d-%06d', $year, $sequence))
            ->all();
    }
}
