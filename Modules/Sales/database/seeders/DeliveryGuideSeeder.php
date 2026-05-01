<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\Sales\Enums\DeliveryGuideCarrierTypeEnum;
use Modules\Sales\Enums\DeliveryGuideTransferReasonEnum;
use Modules\Sales\Models\DeliveryGuide;
use Modules\Sales\Models\DeliveryGuideItem;
use Modules\Sales\Models\DeliveryGuideRecipient;

final class DeliveryGuideSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed 3 demo delivery guides for the first registered company.
     *
     * Idempotency: if any delivery guide already exists for the company, the seeder
     * is skipped entirely to avoid creating duplicate demo data.
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('DeliveryGuideSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        $hasRecords = DB::table('sales_delivery_guides')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasRecords) {
            $this->reportSkipped('0 records created, records already exist');

            return;
        }

        $carrierRoleId = DB::table('core_partner_roles')
            ->where('code', PartnerRoleEnum::CARRIER->value)
            ->value('id');

        $customerRoleId = DB::table('core_partner_roles')
            ->where('code', PartnerRoleEnum::CUSTOMER->value)
            ->value('id');

        $carriers = $carrierRoleId
            ? DB::table('core_business_partners')
                ->select(['core_business_partners.id', 'core_business_partners.legal_name', 'core_business_partners.identification_number'])
                ->join('core_business_partner_role', 'core_business_partners.id', '=', 'core_business_partner_role.business_partner_id')
                ->where('core_business_partner_role.partner_role_id', $carrierRoleId)
                ->where('core_business_partners.company_id', $companyId)
                ->whereNull('core_business_partners.deleted_at')
                ->orderBy('core_business_partners.id')
                ->limit(3)
                ->get()
            : collect();

        $customers = DB::table('core_business_partners')
            ->select(['core_business_partners.id', 'core_business_partners.legal_name', 'core_business_partners.identification_number', 'core_business_partners.identification_type', 'core_business_partners.tax_address'])
            ->join('core_business_partner_role', 'core_business_partners.id', '=', 'core_business_partner_role.business_partner_id')
            ->where('core_business_partner_role.partner_role_id', $customerRoleId)
            ->where('core_business_partners.company_id', $companyId)
            ->whereNull('core_business_partners.deleted_at')
            ->orderBy('core_business_partners.id')
            ->limit(3)
            ->get();

        if ($customers->isEmpty()) {
            $this->command->warn('DeliveryGuideSeeder: No customers found. Skipping.');

            return;
        }

        $products = DB::table('inv_products')
            ->select(['id', 'code', 'name'])
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->limit(3)
            ->get();

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
        $definitions = [
            ['customer_index' => 0, 'carrier_index' => 0, 'date_offset' => -20, 'code_seq' => '000001', 'status' => 'issued'],
            ['customer_index' => 1, 'carrier_index' => 0, 'date_offset' => -10, 'code_seq' => '000002', 'status' => 'issued'],
            ['customer_index' => 2, 'carrier_index' => 0, 'date_offset' => -2,  'code_seq' => '000003', 'status' => 'draft'],
        ];

        $created = 0;

        foreach ($definitions as $def) {
            $customer = $customers->get($def['customer_index']) ?? $customers->first();
            $carrier = $carriers->get($def['carrier_index']);
            $isIssued = $def['status'] === 'issued';
            $transportDate = now()->addDays($def['date_offset'])->toDateString();

            $guide = DeliveryGuide::create([
                'company_id' => $companyId,
                'code' => "GRE-{$year}-{$def['code_seq']}",
                'carrier_id' => $carrier?->id,
                'carrier_name' => $carrier?->legal_name ?? 'Transportista Demo',
                'carrier_identification' => $carrier?->identification_number ?? '9999999999999',
                'carrier_plate' => 'ABC-'.mb_strtoupper(mb_substr(md5($def['code_seq']), 0, 4)),
                'carrier_type' => $carrier ? DeliveryGuideCarrierTypeEnum::ThirdParty : DeliveryGuideCarrierTypeEnum::Own,
                'establishment_code' => $isIssued ? $establishmentCode : null,
                'emission_point_code' => $isIssued ? $emissionPointCode : null,
                'sequential_number' => $isIssued ? mb_str_pad($def['code_seq'], 9, '0', STR_PAD_LEFT) : null,
                'status' => $def['status'],
                'issue_date' => $isIssued ? now()->addDays($def['date_offset'])->toDateString() : now()->toDateString(),
                'transport_start_date' => $transportDate,
                'transport_end_date' => now()->addDays($def['date_offset'] + 1)->toDateString(),
                'origin_address' => 'Bodega Principal, Quito',
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $recipient = DeliveryGuideRecipient::create([
                'delivery_guide_id' => $guide->getKey(),
                'business_partner_id' => $customer->id,
                'recipient_name' => $customer->legal_name,
                'recipient_identification_type' => $customer->identification_type,
                'recipient_identification' => $customer->identification_number,
                'destination_address' => $customer->tax_address ?? 'Destino Demo',
                'transfer_reason' => DeliveryGuideTransferReasonEnum::Sale,
                'sort_order' => 1,
            ]);

            $productsToUse = $products->isEmpty()
                ? collect([['code' => 'PROD-001', 'name' => 'Producto Demo', 'id' => null]])
                : $products;

            foreach ($productsToUse->take(2) as $index => $product) {
                DeliveryGuideItem::create([
                    'delivery_guide_recipient_id' => $recipient->getKey(),
                    'product_id' => is_object($product) ? $product->id : null,
                    'product_code' => is_object($product) ? $product->code : $product['code'],
                    'product_name' => is_object($product) ? $product->name : $product['name'],
                    'quantity' => '10.00',
                    'sort_order' => $index + 1,
                ]);
            }

            $created++;
        }

        $this->reportCreated($created);
    }
}
