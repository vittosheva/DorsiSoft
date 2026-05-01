<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Finance\Enums\CollectionMethodEnum;
use Modules\Finance\Models\Collection;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;

final class CollectionSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed 15 demo collections against existing invoices.
     *
     * Code format: COB-YEAR-NNNNNN (HasYearlyAutoCode). The code is generated
     * manually because WithoutModelEvents in DatabaseSeeder suppresses the
     * creating event used by HasYearlyAutoCode.
     *
     * Idempotency: if any collection already exists for the company, the seeder
     * is skipped entirely to avoid creating duplicate demo data.
     *
     * Collection scenarios:
     * - Full collections for individual invoices
     * - Partial collections for invoices
     * - Various collection methods and dates
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('PaymentSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        // Idempotency guard
        $hasCollections = DB::table('sales_collections')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasCollections) {
            $this->reportSkipped('0 records created, records already exist');

            return;
        }

        // Fetch all Issued and Paid invoices
        $invoices = Invoice::withoutGlobalScopes()
            ->select(['id', 'business_partner_id', 'customer_name', 'currency_code', 'total', 'paid_amount', 'status'])
            ->where('company_id', $companyId)
            ->whereIn('status', [InvoiceStatusEnum::Issued, InvoiceStatusEnum::Paid])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        if ($invoices->count() < 10) {
            $this->command->warn('CollectionSeeder: Less than 10 Issued/Paid invoices found. Run InvoiceSeeder with more data. Skipping.');

            return;
        }

        $year = now()->year;
        $created = 0;
        $collectionMethods = [
            CollectionMethodEnum::BankTransfer->value,
            CollectionMethodEnum::Cash->value,
            CollectionMethodEnum::CreditCard->value,
            CollectionMethodEnum::Check->value,
        ];

        // Create 15 collections with various allocation patterns.
        $collectionConfigs = [
            // Full collections
            ['invoiceIndex' => 7, 'percentage' => 100, 'method' => 0, 'daysBack' => 15, 'reference' => 'TRF-001'],
            ['invoiceIndex' => 8, 'percentage' => 100, 'method' => 1, 'daysBack' => 14, 'reference' => 'CHK-001'],
            ['invoiceIndex' => 9, 'percentage' => 100, 'method' => 2, 'daysBack' => 13, 'reference' => 'CRD-001'],
            ['invoiceIndex' => 10, 'percentage' => 100, 'method' => 3, 'daysBack' => 12, 'reference' => 'CHK-002'],
            ['invoiceIndex' => 11, 'percentage' => 100, 'method' => 0, 'daysBack' => 11, 'reference' => 'TRF-002'],
            ['invoiceIndex' => 12, 'percentage' => 100, 'method' => 1, 'daysBack' => 10, 'reference' => 'TRF-003'],
            ['invoiceIndex' => 6, 'percentage' => 60, 'method' => 2, 'daysBack' => 16, 'reference' => 'CRD-005'],
            // Partial collections
            ['invoiceIndex' => 2, 'percentage' => 70, 'method' => 2, 'daysBack' => 9, 'reference' => 'CRD-002'],
            ['invoiceIndex' => 3, 'percentage' => 70, 'method' => 0, 'daysBack' => 8, 'reference' => 'TRF-004'],
            ['invoiceIndex' => 4, 'percentage' => 75, 'method' => 1, 'daysBack' => 7, 'reference' => 'CHK-003'],
            ['invoiceIndex' => 5, 'percentage' => 40, 'method' => 3, 'daysBack' => 6, 'reference' => 'CHK-004'],
            ['invoiceIndex' => 6, 'percentage' => 40, 'method' => 2, 'daysBack' => 5, 'reference' => 'CRD-003'],
            ['invoiceIndex' => 0, 'percentage' => 50, 'method' => 0, 'daysBack' => 4, 'reference' => 'TRF-005'],
            ['invoiceIndex' => 1, 'percentage' => 65, 'method' => 1, 'daysBack' => 3, 'reference' => 'CHK-005'],
            ['invoiceIndex' => 2, 'percentage' => 30, 'method' => 2, 'daysBack' => 2, 'reference' => 'CRD-004'],
        ];

        $paidByInvoice = [];
        foreach ($invoices as $invoice) {
            $paidByInvoice[$invoice->id] = (string) ($invoice->paid_amount ?? '0');
        }

        foreach ($collectionConfigs as $config) {
            $invoice = $invoices->get($config['invoiceIndex']);

            if (! $invoice) {
                continue;
            }

            $targetAmount = bcmul((string) $invoice->total, (string) ($config['percentage'] / 100), 4);
            $alreadyPaid = $paidByInvoice[$invoice->id] ?? '0';
            $remainingAmount = bcsub((string) $invoice->total, $alreadyPaid, 4);

            if (bccomp($remainingAmount, '0', 4) <= 0) {
                continue;
            }

            $allocationAmount = bccomp($targetAmount, $remainingAmount, 4) === 1
                ? $remainingAmount
                : $targetAmount;

            if (bccomp($allocationAmount, '0', 4) <= 0) {
                continue;
            }

            $method = $collectionMethods[$config['method']] ?? CollectionMethodEnum::BankTransfer->value;

            $collection = Collection::create([
                'company_id' => $companyId,
                'code' => "COB-{$year}-".mb_str_pad((string) (++$created), 6, '0', STR_PAD_LEFT),
                'business_partner_id' => $invoice->business_partner_id,
                'customer_name' => $invoice->customer_name,
                'collection_date' => now()->subDays($config['daysBack'])->toDateString(),
                'amount' => $allocationAmount,
                'currency_code' => $invoice->currency_code,
                'collection_method' => $method,
                'reference_number' => $config['reference'],
                'notes' => $config['percentage'] === 100 ? 'Cobro completo demo.' : "Cobro parcial ({$config['percentage']}%) demo.",
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            CollectionAllocation::withoutEvents(function () use ($collection, $invoice, $allocationAmount, $companyId): void {
                CollectionAllocation::create([
                    'company_id' => $companyId,
                    'collection_id' => $collection->getKey(),
                    'invoice_id' => $invoice->id,
                    'amount' => $allocationAmount,
                    'allocated_at' => $collection->collection_date,
                ]);
            });

            // Update paid_amount on invoice
            $paidByInvoice[$invoice->id] = bcadd($alreadyPaid, $allocationAmount, 4);

            Invoice::withoutGlobalScopes()
                ->where('id', $invoice->id)
                ->update(['paid_amount' => $paidByInvoice[$invoice->id]]);
        }

        $this->reportCreated($created);
    }
}
