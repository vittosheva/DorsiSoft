<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Enums\SriPaymentMethodEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceItem;
use Modules\Sales\Models\InvoiceItemTax;
use Modules\Sales\Services\InvoiceTotalsCalculator;

final class InvoiceSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed 15 demo invoices for the first registered company.
     *
     * Code format: FAC-YEAR-NNNNNN (HasYearlyAutoCode). The code is generated
     * manually because WithoutModelEvents in DatabaseSeeder suppresses the
     * creating event used by HasYearlyAutoCode.
     *
     * Idempotency: if any invoice already exists for the company, the seeder
     * is skipped entirely to avoid creating duplicate demo data.
     *
     * Mix of Draft, Issued, and Paid invoices for realistic payment testing:
     * - 3 Draft invoices (not payable yet)
     * - 5 Issued invoices (awaiting payment)
     * - 7 Paid invoices (for payment allocation demos)
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('InvoiceSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        // Idempotency guard
        $hasInvoices = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasInvoices) {
            $this->reportSkipped('0 records created, records already exist');

            return;
        }

        // Customers

        $customerRoleId = DB::table('core_partner_roles')
            ->where('code', PartnerRoleEnum::CUSTOMER->value)
            ->value('id');

        if (! $customerRoleId) {
            $this->command->warn('InvoiceSeeder: No customer role found. Run PartnerRoleSeeder first. Skipping.');

            return;
        }

        $customers = DB::table('core_business_partners')
            ->join('core_business_partner_role', 'core_business_partners.id', '=', 'core_business_partner_role.business_partner_id')
            ->where('core_business_partner_role.partner_role_id', $customerRoleId)
            ->where('core_business_partners.company_id', $companyId)
            ->whereNull('core_business_partners.deleted_at')
            ->orderBy('core_business_partners.id')
            ->limit(10)
            ->get([
                'core_business_partners.id',
                'core_business_partners.legal_name',
                'core_business_partners.trade_name',
                'core_business_partners.identification_type',
                'core_business_partners.identification_number',
                'core_business_partners.tax_address',
                'core_business_partners.email',
                'core_business_partners.phone',
                'core_business_partners.mobile',
            ]);

        if ($customers->isEmpty()) {
            $this->command->warn('InvoiceSeeder: No customers found. Skipping.');

            return;
        }

        // Products with unit and tax
        $products = DB::table('inv_products')
            ->leftJoin('inv_units', 'inv_products.unit_id', '=', 'inv_units.id')
            ->leftJoin('fin_taxes', 'inv_products.tax_id', '=', 'fin_taxes.id')
            ->where('inv_products.company_id', $companyId)
            ->where('inv_products.is_active', true)
            ->whereNull('inv_products.deleted_at')
            ->orderBy('inv_products.id')
            ->get([
                'inv_products.id',
                'inv_products.code as product_code',
                'inv_products.name as product_name',
                'inv_products.sale_price',
                'inv_units.symbol as product_unit',
                'fin_taxes.id as tax_id',
                'fin_taxes.name as tax_name',
                'fin_taxes.type as tax_type',
                'fin_taxes.sri_code as tax_code',
                'fin_taxes.sri_percentage_code as tax_percentage_code',
                'fin_taxes.rate as tax_rate',
                'fin_taxes.calculation_type as tax_calculation_type',
            ]);

        $products = $this->takeProductsForDemo($products);

        if ($products->isEmpty()) {
            $this->command->warn('InvoiceSeeder: No products found. Skipping.');

            return;
        }

        // Seller
        $seller = DB::table('core_users')
            ->select(['id', 'name'])
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        $establishmentCode = DB::table('core_establishments')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('code') ?? '001';

        $emissionPointCode = DB::table('core_emission_points')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('code') ?? '001';

        $year = now()->year;
        $calculator = app(InvoiceTotalsCalculator::class);

        $definitions = [
            // Draft invoices (2)
            [
                'status' => InvoiceStatusEnum::Draft,
                'customer_index' => 0,
                'product_indices' => [0, 1],
                'date_offset' => 2,
                'issue_date' => now()->addDays(2)->toDateString(),
                'code_seq' => '000001',
            ],
            [
                'status' => InvoiceStatusEnum::Draft,
                'customer_index' => 1,
                'product_indices' => [1, 2],
                'date_offset' => 1,
                'issue_date' => now()->addDays(1)->toDateString(),
                'code_seq' => '000002',
            ],
            // Issued invoices (6)
            [
                'status' => InvoiceStatusEnum::Issued,
                'customer_index' => 2,
                'product_indices' => [0],
                'date_offset' => -5,
                'issue_date' => now()->subDays(5)->toDateString(),
                'code_seq' => '000003',
            ],
            [
                'status' => InvoiceStatusEnum::Issued,
                'customer_index' => 3,
                'product_indices' => [1, 2],
                'date_offset' => -8,
                'issue_date' => now()->subDays(8)->toDateString(),
                'code_seq' => '000004',
            ],
            [
                'status' => InvoiceStatusEnum::Issued,
                'customer_index' => 4,
                'product_indices' => [2, 3],
                'date_offset' => -10,
                'issue_date' => now()->subDays(10)->toDateString(),
                'code_seq' => '000005',
            ],
            [
                'status' => InvoiceStatusEnum::Issued,
                'customer_index' => 5,
                'product_indices' => [0, 1, 3],
                'date_offset' => -7,
                'issue_date' => now()->subDays(7)->toDateString(),
                'code_seq' => '000006',
            ],
            [
                'status' => InvoiceStatusEnum::Issued,
                'customer_index' => 6,
                'product_indices' => [4],
                'date_offset' => -12,
                'issue_date' => now()->subDays(12)->toDateString(),
                'code_seq' => '000007',
            ],
            [
                'status' => InvoiceStatusEnum::Issued,
                'customer_index' => 7,
                'product_indices' => [1, 2],
                'date_offset' => -6,
                'issue_date' => now()->subDays(6)->toDateString(),
                'code_seq' => '000008',
            ],
            // Paid invoices (7)
            [
                'status' => InvoiceStatusEnum::Paid,
                'customer_index' => 8,
                'product_indices' => [0],
                'date_offset' => -15,
                'issue_date' => now()->subDays(15)->toDateString(),
                'code_seq' => '000009',
            ],
            [
                'status' => InvoiceStatusEnum::Paid,
                'customer_index' => 9,
                'product_indices' => [2, 3],
                'date_offset' => -20,
                'issue_date' => now()->subDays(20)->toDateString(),
                'code_seq' => '000010',
            ],
            [
                'status' => InvoiceStatusEnum::Paid,
                'customer_index' => 0,
                'product_indices' => [1, 4],
                'date_offset' => -25,
                'issue_date' => now()->subDays(25)->toDateString(),
                'code_seq' => '000011',
            ],
            [
                'status' => InvoiceStatusEnum::Paid,
                'customer_index' => 1,
                'product_indices' => [0, 2, 3],
                'date_offset' => -18,
                'issue_date' => now()->subDays(18)->toDateString(),
                'code_seq' => '000012',
            ],
            [
                'status' => InvoiceStatusEnum::Paid,
                'customer_index' => 2,
                'product_indices' => [3],
                'date_offset' => -30,
                'issue_date' => now()->subDays(30)->toDateString(),
                'code_seq' => '000013',
            ],
            [
                'status' => InvoiceStatusEnum::Paid,
                'customer_index' => 3,
                'product_indices' => [0, 1],
                'date_offset' => -22,
                'issue_date' => now()->subDays(22)->toDateString(),
                'code_seq' => '000014',
            ],
            [
                'status' => InvoiceStatusEnum::Paid,
                'customer_index' => 4,
                'product_indices' => [1, 2],
                'date_offset' => -28,
                'issue_date' => now()->subDays(28)->toDateString(),
                'code_seq' => '000015',
            ],
        ];

        $created = 0;

        foreach ($definitions as $def) {
            $customer = $customers->get($def['customer_index']) ?? $customers->first();
            $date = now()->addDays($def['date_offset'])->toDateString();
            $code = "FAC-{$year}-{$def['code_seq']}";
            $isIssuedDocument = in_array($def['status'], [InvoiceStatusEnum::Issued, InvoiceStatusEnum::Paid], true);
            $sequentialNumber = $isIssuedDocument
                ? mb_str_pad((string) ((int) $def['code_seq']), 9, '0', STR_PAD_LEFT)
                : null;

            $additionalInfo = array_values(array_filter([
                [
                    'name' => 'Email',
                    'value' => $customer->email,
                ],
                [
                    'name' => 'Telefono',
                    'value' => $customer->phone ?? $customer->mobile,
                ],
            ], static fn (array $item): bool => filled($item['value'])));

            $invoice = Invoice::create([
                'company_id' => $companyId,
                'code' => $code,
                'business_partner_id' => $customer->id,
                'customer_name' => $customer->legal_name,
                'customer_trade_name' => $customer->trade_name,
                'customer_identification_type' => $customer->identification_type,
                'customer_identification' => $customer->identification_number,
                'customer_address' => $customer->tax_address,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone ?? $customer->mobile,
                'seller_id' => $seller?->id,
                'seller_name' => $seller?->name,
                'currency_code' => 'USD',
                'status' => $def['status'],
                'issue_date' => $def['issue_date'] ?? $date,
                'subtotal' => 0,
                'tax_base' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'paid_amount' => '0.0000',
                'establishment_code' => $isIssuedDocument ? $establishmentCode : null,
                'emission_point_code' => $isIssuedDocument ? $emissionPointCode : null,
                'sequential_number' => $sequentialNumber,
                'access_key' => null,
                'sri_payments' => $isIssuedDocument ? [] : null,
                'additional_info' => $isIssuedDocument ? $additionalInfo : null,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $sortOrder = 1;
            foreach ($def['product_indices'] as $productIndex) {
                $product = $products->get($productIndex) ?? $products->first();
                $unitPrice = $product->sale_price ?? '10.0000';

                $item = InvoiceItem::create([
                    'invoice_id' => $invoice->getKey(),
                    'product_id' => $product->id,
                    'product_code' => $product->product_code,
                    'product_name' => $product->product_name,
                    'product_unit' => $product->product_unit,
                    'sort_order' => $sortOrder++,
                    'description' => $product->product_name,
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'subtotal' => 0,
                    'total' => 0,
                ]);

                if ($product->tax_id) {
                    InvoiceItemTax::create([
                        'invoice_item_id' => $item->getKey(),
                        'tax_id' => $product->tax_id,
                        'tax_name' => $product->tax_name ?? '',
                        'tax_type' => $product->tax_type ?? 'percentage',
                        'tax_code' => $product->tax_code,
                        'tax_percentage_code' => $product->tax_percentage_code,
                        'tax_rate' => $product->tax_rate,
                        'tax_calculation_type' => $product->tax_calculation_type,
                        'base_amount' => 0,
                        'tax_amount' => 0,
                    ]);
                }
            }

            $reloadedInvoice = Invoice::with(['items.taxes'])->findOrFail($invoice->getKey());
            $calculator->recalculate($reloadedInvoice);

            if ($isIssuedDocument) {
                $invoiceAttributes = [
                    'sri_payments' => [[
                        'method' => SriPaymentMethodEnum::BankTransfer->value,
                        'amount' => (string) $reloadedInvoice->total,
                    ]],
                ];

                if ($def['status'] === InvoiceStatusEnum::Paid) {
                    $invoiceAttributes['paid_amount'] = (string) $reloadedInvoice->total;
                }

                $reloadedInvoice->update($invoiceAttributes);
            }

            $created++;
        }

        $this->reportCreated($created);
    }

    /**
     * @param  Collection<int, object>  $products
     * @return Collection<int, object>
     */
    private function takeProductsForDemo(Collection $products): Collection
    {
        $positiveRates = $products
            ->pluck('tax_rate')
            ->filter(static fn (mixed $rate): bool => (float) $rate > 0)
            ->map(static fn (mixed $rate): float => (float) $rate)
            ->unique()
            ->sortDesc()
            ->values();

        $positiveBuckets = $positiveRates
            ->map(fn (float $rate): Collection => $products
                ->filter(static fn (object $product): bool => (float) ($product->tax_rate ?? 0) === $rate)
                ->values());

        $zeroRateProducts = $products
            ->filter(static fn (object $product): bool => (string) ($product->tax_type ?? '') === 'IVA' && (float) ($product->tax_rate ?? 0) === 0.0)
            ->values();

        $firstPass = collect();

        foreach ($positiveBuckets as $bucket) {
            if ($bucket->isNotEmpty()) {
                $firstPass->push($bucket->first());
            }
        }

        if ($zeroRateProducts->isNotEmpty()) {
            $firstPass->push($zeroRateProducts->first());
        }

        $secondPass = collect();

        foreach ($positiveBuckets as $bucket) {
            if ($bucket->count() > 1) {
                $secondPass->push($bucket->get(1));
            }
        }

        if ($zeroRateProducts->count() > 1) {
            $secondPass->push($zeroRateProducts->get(1));
        }

        return $firstPass
            ->merge($secondPass)
            ->merge($products)
            ->unique('id')
            ->values();
    }
}
