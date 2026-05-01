<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\Sales\Enums\DiscountTypeEnum;
use Modules\Sales\Enums\QuotationStatusEnum;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\QuotationItem;
use Modules\Sales\Models\QuotationItemTax;
use Modules\Sales\Services\QuotationTotalsCalculator;

final class QuotationSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed 4 demo quotations for the first registered company.
     *
     * Code format: COT-YEAR-NNNNNN (HasYearlyAutoCode). The code is generated
     * manually because WithoutModelEvents in DatabaseSeeder suppresses the
     * creating event used by HasYearlyAutoCode.
     *
     * Idempotency: if any quotation already exists for the company, the seeder
     * is skipped entirely to avoid creating duplicate demo data.
     *
     * Q1 — Draft   : customer 1, Lista General, 2 items
     * Q2 — Sent    : customer 2, Lista VIP,     3 items
     * Q3 — Accepted: customer 3, Lista General, 2 items
     * Q4 — Draft   : customer 4, no price list, 1 item
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('QuotationSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        // Idempotency guard — skip if demo quotations already exist
        $hasQuotations = DB::table('sales_quotations')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasQuotations) {
            $this->reportSkipped('0 records created, records already exist');

            return;
        }

        // Customers (with snapshot-ready fields) ------------------------------

        $customerRoleId = DB::table('core_partner_roles')
            ->where('code', PartnerRoleEnum::CUSTOMER->value)
            ->value('id');

        if (! $customerRoleId) {
            $this->command->warn('QuotationSeeder: No customer role found. Run PartnerRoleSeeder first. Skipping.');

            return;
        }

        $customers = DB::table('core_business_partners as bp')
            ->join('core_business_partner_role as bpr', 'bpr.business_partner_id', '=', 'bp.id')
            ->where('bp.company_id', $companyId)
            ->whereNull('bp.deleted_at')
            ->where('bpr.partner_role_id', $customerRoleId)
            ->orderBy('bp.id')
            ->select([
                'bp.id',
                'bp.legal_name',
                'bp.trade_name',
                'bp.identification_type',
                'bp.identification_number',
                'bp.tax_address',
                'bp.email',
                'bp.phone',
                'bp.mobile',
            ])
            ->get();

        if ($customers->isEmpty()) {
            $this->command->warn('QuotationSeeder: No customers found. Run BusinessPartnerSeeder first. Skipping.');

            return;
        }

        // Products with unit and tax snapshot data ----------------------------
        $products = DB::table('inv_products as p')
            ->leftJoin('inv_units as u', function ($join): void {
                $join->on('u.id', '=', 'p.unit_id')->whereNull('u.deleted_at');
            })
            ->leftJoin('fin_taxes as t', function ($join): void {
                $join->on('t.id', '=', 'p.tax_id')->whereNull('t.deleted_at');
            })
            ->where('p.company_id', $companyId)
            ->whereNull('p.deleted_at')
            ->where('p.is_for_sale', true)
            ->where('p.is_active', true)
            ->orderBy('p.id')
            ->select([
                'p.id',
                'p.code',
                'p.name',
                'p.sale_price',
                'u.symbol as unit_symbol',
                't.id as tax_id',
                't.name as tax_name',
                't.type as tax_type',
                't.sri_code as tax_code',
                't.sri_percentage_code as tax_percentage_code',
                't.rate as tax_rate',
                't.calculation_type as tax_calculation_type',
            ])
            ->get();

        if ($products->isEmpty()) {
            $this->command->warn('QuotationSeeder: No products found. Run ProductSeeder first. Skipping.');

            return;
        }

        // Price lists ---------------------------------------------------------
        $priceLists = DB::table('sales_price_lists')
            ->select(['id', 'name'])
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->get();

        $generalList = $priceLists->firstWhere('name', 'Lista General');
        $vipList = $priceLists->firstWhere('name', 'Lista VIP');

        // Seller (first active user of the company) ---------------------------
        $seller = DB::table('core_users')
            ->select(['id', 'name'])
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->first();

        // Yearly code generation ----------------------------------------------
        $year = now()->year;
        $yearlyPrefix = "COT-{$year}-";

        $maxCode = DB::table('sales_quotations')
            ->where('company_id', $companyId)
            ->where('code', 'LIKE', "{$yearlyPrefix}%")
            ->max('code');

        $seq = $maxCode ? ((int) mb_substr($maxCode, mb_strlen($yearlyPrefix))) + 1 : 1;

        /**
         * @var array<int, array{
         *   status: QuotationStatusEnum,
         *   offsetDays: int,
         *   validity_days: int,
         *   customer_index: int,
         *   price_list: object{id: int, name: string}|null,
         *   product_indices: list<int>,
         *   quantities: list<float>,
         * }> $definitions
         */
        $definitions = [
            [
                'status' => QuotationStatusEnum::Draft,
                'offsetDays' => 3,
                'validity_days' => 30,
                'customer_index' => 0,
                'price_list' => $generalList,
                'product_indices' => [0, 1],
                'quantities' => [1.0, 2.0],
            ],
            [
                'status' => QuotationStatusEnum::Sent,
                'offsetDays' => 7,
                'validity_days' => 15,
                'customer_index' => 1,
                'price_list' => $vipList ?? $generalList,
                'product_indices' => [2, 3, 4],
                'quantities' => [2.0, 3.0, 1.0],
            ],
            [
                'status' => QuotationStatusEnum::Accepted,
                'offsetDays' => 14,
                'validity_days' => 30,
                'customer_index' => 2,
                'price_list' => $generalList,
                'product_indices' => [0, 5],
                'quantities' => [1.0, 1.0],
            ],
            [
                'status' => QuotationStatusEnum::Draft,
                'offsetDays' => 0,
                'validity_days' => 30,
                'customer_index' => 3,
                'price_list' => null,
                'product_indices' => [6],
                'quantities' => [2.0],
            ],
        ];

        $created = 0;
        $calculator = app(QuotationTotalsCalculator::class);

        foreach ($definitions as $def) {
            $customer = $customers->get(
                min($def['customer_index'], $customers->count() - 1)
            );

            if (! $customer) {
                continue;
            }

            $date = now()->subDays($def['offsetDays']);
            $expiresAt = $date->copy()->addDays($def['validity_days']);

            // Build line items ------------------------------------------------
            $items = [];
            $sortOrder = 1;

            foreach ($def['product_indices'] as $idx => $productIdx) {
                $product = $products->get(
                    min($productIdx, $products->count() - 1)
                );

                if (! $product) {
                    continue;
                }

                $qty = $def['quantities'][$idx] ?? 1.0;
                $unitPrice = (float) $product->sale_price;

                $items[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'sort_order' => $sortOrder++,
                ];
            }

            // Decode email JSON stored by BusinessPartner (array cast) --------
            $customerEmail = null;
            if (is_string($customer->email)) {
                $decoded = json_decode($customer->email, true);
                $customerEmail = is_array($decoded) ? ($decoded[0] ?? null) : $customer->email;
            }

            $isSentOrAccepted = in_array($def['status'], [QuotationStatusEnum::Sent, QuotationStatusEnum::Accepted], true);
            $isAccepted = $def['status'] === QuotationStatusEnum::Accepted;

            $code = $yearlyPrefix.mb_str_pad((string) $seq++, 6, '0', STR_PAD_LEFT);

            $quotation = Quotation::create([
                'company_id' => $companyId,
                'code' => $code,
                'business_partner_id' => $customer->id,
                'customer_name' => $customer->legal_name,
                'customer_trade_name' => $customer->trade_name,
                'customer_identification_type' => $customer->identification_type,
                'customer_identification' => $customer->identification_number,
                'customer_address' => $customer->tax_address,
                'customer_email' => $customerEmail,
                'customer_phone' => $customer->phone ?? $customer->mobile,
                'seller_id' => $seller?->id,
                'seller_name' => $seller?->name,
                'price_list_id' => $def['price_list']?->id,
                'price_list_name' => $def['price_list']?->name,
                'currency_code' => 'USD',
                'status' => $def['status'],
                'issue_date' => $date->toDateString(),
                'validity_days' => $def['validity_days'],
                'expires_at' => $expiresAt->toDateString(),
                'discount_type' => DiscountTypeEnum::Percentage,
                'discount_value' => 0,
                'subtotal' => 0,
                'tax_base' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'sent_at' => $isSentOrAccepted ? $date->copy()->addDay() : null,
                'accepted_at' => $isAccepted ? $date->copy()->addDays(3) : null,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Line items + taxes -----------------------------------------------
            foreach ($items as $itemData) {
                $product = $itemData['product'];

                $item = QuotationItem::create([
                    'quotation_id' => $quotation->getKey(),
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'product_unit' => $product->unit_symbol,
                    'sort_order' => $itemData['sort_order'],
                    'description' => $product->name,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_type' => DiscountTypeEnum::Percentage,
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'subtotal' => 0,
                    'total' => 0,
                ]);

                if ($product->tax_id) {
                    QuotationItemTax::create([
                        'quotation_item_id' => $item->getKey(),
                        'tax_id' => $product->tax_id,
                        'tax_name' => $product->tax_name,
                        'tax_type' => $product->tax_type,
                        'tax_code' => $product->tax_code,
                        'tax_percentage_code' => $product->tax_percentage_code,
                        'tax_rate' => $product->tax_rate,
                        'tax_calculation_type' => $product->tax_calculation_type,
                        'base_amount' => 0,
                        'tax_amount' => 0,
                    ]);
                }
            }

            $calculator->recalculate(Quotation::with(['items.taxes'])->findOrFail($quotation->getKey()));

            $created++;
        }

        $this->reportCreated($created);
    }
}
