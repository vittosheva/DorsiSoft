<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\Sales\Enums\QuotationStatusEnum;
use Modules\Sales\Enums\SalesOrderStatusEnum;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\SalesOrderItemTax;
use Modules\Sales\Services\SalesOrderTotalsCalculator;

final class SalesOrderSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed 3 demo sales orders for the first registered company.
     *
     * Code format: ORD-YEAR-NNNNNN (HasYearlyAutoCode). The code is generated
     * manually because WithoutModelEvents in DatabaseSeeder suppresses the
     * creating event used by HasYearlyAutoCode.
     *
     * Idempotency: if any sales order already exists for the company, the seeder
     * is skipped entirely to avoid creating duplicate demo data.
     *
     * O1 — Pending  : demo customer, 2 items
     * O2 — Confirmed: linked to first accepted quotation when available
     * O3 — Completed: demo customer, 1 item
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('SalesOrderSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        // Idempotency guard — skip if demo orders already exist
        $hasOrders = DB::table('sales_orders')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasOrders) {
            $this->reportSkipped('0 records created, records already exist');

            return;
        }

        // Customers

        $customerRoleId = DB::table('core_partner_roles')
            ->where('code', PartnerRoleEnum::CUSTOMER->value)
            ->value('id');

        if (! $customerRoleId) {
            $this->command->warn('SalesOrderSeeder: No customer role found. Run CoreDatabaseSeeder first. Skipping.');

            return;
        }

        $customers = DB::table('core_business_partners')
            ->join('core_business_partner_role', 'core_business_partners.id', '=', 'core_business_partner_role.business_partner_id')
            ->where('core_business_partner_role.partner_role_id', $customerRoleId)
            ->where('core_business_partners.company_id', $companyId)
            ->whereNull('core_business_partners.deleted_at')
            ->orderBy('core_business_partners.id')
            ->limit(3)
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
            $this->command->warn('SalesOrderSeeder: No customers found. Skipping.');

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
            ->limit(5)
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

        if ($products->isEmpty()) {
            $this->command->warn('SalesOrderSeeder: No products found. Skipping.');

            return;
        }

        // Seller
        $seller = DB::table('core_users')
            ->select(['id', 'name'])
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        $year = now()->year;
        $calculator = app(SalesOrderTotalsCalculator::class);
        $acceptedQuotation = Quotation::query()
            ->where('company_id', $companyId)
            ->where('status', QuotationStatusEnum::Accepted)
            ->with(['items.taxes'])
            ->orderBy('id')
            ->first();

        $definitions = [
            [
                'status' => SalesOrderStatusEnum::Pending,
                'customer_index' => 0,
                'product_indices' => [0, 1],
                'date_offset' => -2,
                'code_seq' => '000001',
                'quotation' => null,
            ],
            [
                'status' => SalesOrderStatusEnum::Confirmed,
                'customer_index' => 1,
                'product_indices' => [1, 2],
                'date_offset' => -10,
                'code_seq' => '000002',
                'quotation' => $acceptedQuotation,
            ],
            [
                'status' => SalesOrderStatusEnum::Completed,
                'customer_index' => 2,
                'product_indices' => [0],
                'date_offset' => -20,
                'code_seq' => '000003',
                'quotation' => null,
            ],
        ];

        $created = 0;

        foreach ($definitions as $def) {
            $customer = $customers->get($def['customer_index']) ?? $customers->first();
            /** @var Quotation|null $quotation */
            $quotation = $def['quotation'];
            $date = now()->addDays($def['date_offset'])->toDateString();
            $code = "ORD-{$year}-{$def['code_seq']}";

            $order = SalesOrder::create([
                'company_id' => $companyId,
                'code' => $code,
                'quotation_id' => $quotation?->getKey(),
                'business_partner_id' => $quotation?->business_partner_id ?? $customer->id,
                'customer_name' => $quotation?->customer_name ?? $customer->legal_name,
                'customer_trade_name' => $quotation?->customer_trade_name ?? $customer->trade_name,
                'customer_identification_type' => $quotation?->customer_identification_type ?? $customer->identification_type,
                'customer_identification' => $quotation?->customer_identification ?? $customer->identification_number,
                'customer_address' => $quotation?->customer_address ?? $customer->tax_address,
                'customer_email' => self::normalizeCustomerEmail($quotation?->customer_email ?? $customer->email),
                'customer_phone' => $quotation?->customer_phone ?? ($customer->phone ?? $customer->mobile),
                'seller_id' => $seller?->id,
                'seller_name' => $quotation?->seller_name ?? $seller?->name,
                'currency_code' => 'USD',
                'status' => $def['status'],
                'issue_date' => $date,
                'subtotal' => 0,
                'tax_base' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            if ($quotation) {
                foreach ($quotation->items as $quotationItem) {
                    $orderItem = SalesOrderItem::create([
                        'order_id' => $order->getKey(),
                        'product_id' => $quotationItem->product_id,
                        'product_code' => $quotationItem->product_code,
                        'product_name' => $quotationItem->product_name,
                        'product_unit' => $quotationItem->product_unit,
                        'sort_order' => $quotationItem->sort_order,
                        'description' => $quotationItem->description,
                        'detail_1' => $quotationItem->detail_1,
                        'detail_2' => $quotationItem->detail_2,
                        'quantity' => $quotationItem->quantity,
                        'unit_price' => $quotationItem->unit_price,
                        'discount_type' => $quotationItem->discount_type?->value,
                        'discount_value' => $quotationItem->discount_value,
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'subtotal' => 0,
                        'total' => 0,
                    ]);

                    foreach ($quotationItem->taxes as $tax) {
                        SalesOrderItemTax::create([
                            'order_item_id' => $orderItem->getKey(),
                            'tax_id' => $tax->tax_id,
                            'tax_name' => $tax->tax_name,
                            'tax_type' => $tax->tax_type,
                            'tax_code' => $tax->tax_code,
                            'tax_percentage_code' => $tax->tax_percentage_code,
                            'tax_rate' => $tax->tax_rate,
                            'tax_calculation_type' => $tax->tax_calculation_type?->value ?? $tax->tax_calculation_type,
                            'base_amount' => 0,
                            'tax_amount' => 0,
                        ]);
                    }
                }

                $quotation->order_id = $order->getKey();
                $quotation->saveQuietly();
            } else {
                $sortOrder = 1;

                foreach ($def['product_indices'] as $productIndex) {
                    $product = $products->get($productIndex) ?? $products->first();
                    $quantity = 1;
                    $unitPrice = $product->sale_price ?? '10.0000';

                    $orderItem = SalesOrderItem::create([
                        'order_id' => $order->getKey(),
                        'product_id' => $product->id,
                        'product_code' => $product->product_code,
                        'product_name' => $product->product_name,
                        'product_unit' => $product->product_unit,
                        'sort_order' => $sortOrder++,
                        'description' => $product->product_name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'subtotal' => 0,
                        'total' => 0,
                    ]);

                    if ($product->tax_id) {
                        SalesOrderItemTax::create([
                            'order_item_id' => $orderItem->getKey(),
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
            }

            $calculator->recalculate(SalesOrder::with(['items.taxes'])->findOrFail($order->getKey()));

            $created++;
        }

        $this->reportCreated($created);
    }

    private static function normalizeCustomerEmail(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            }
        }

        if (is_array($value)) {
            $firstEmail = collect($value)
                ->first(static fn (mixed $email): bool => filled($email));

            return filled($firstEmail) ? (string) $firstEmail : null;
        }

        return (string) $value;
    }
}
