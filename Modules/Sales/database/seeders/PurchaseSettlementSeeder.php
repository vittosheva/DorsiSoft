<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Models\PurchaseSettlementItem;

final class PurchaseSettlementSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed 3 demo purchase settlements for the first registered company.
     *
     * Idempotency: if any purchase settlement already exists for the company, the seeder
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
            $this->command->warn('PurchaseSettlementSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        $hasRecords = DB::table('sales_purchase_settlements')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasRecords) {
            $this->reportSkipped('0 records created, records already exist');

            return;
        }

        $supplierRoleId = DB::table('core_partner_roles')
            ->where('code', PartnerRoleEnum::SUPPLIER->value)
            ->value('id');

        $suppliers = $supplierRoleId
            ? DB::table('core_business_partners')
                ->select(['core_business_partners.id', 'core_business_partners.legal_name', 'core_business_partners.identification_number', 'core_business_partners.identification_type', 'core_business_partners.tax_address'])
                ->join('core_business_partner_role', 'core_business_partners.id', '=', 'core_business_partner_role.business_partner_id')
                ->where('core_business_partner_role.partner_role_id', $supplierRoleId)
                ->where('core_business_partners.company_id', $companyId)
                ->whereNull('core_business_partners.deleted_at')
                ->orderBy('core_business_partners.id')
                ->limit(3)
                ->get()
            : collect();

        if ($suppliers->isEmpty()) {
            $this->command->warn('PurchaseSettlementSeeder: No suppliers found. Skipping.');

            return;
        }

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
            ['supplier_index' => 0, 'date_offset' => -25, 'code_seq' => '000001', 'status' => 'issued', 'subtotal' => '150.00', 'tax_base' => '150.00', 'tax_amount' => '18.00', 'total' => '168.00'],
            ['supplier_index' => 1, 'date_offset' => -10, 'code_seq' => '000002', 'status' => 'issued', 'subtotal' => '320.00', 'tax_base' => '320.00', 'tax_amount' => '38.40', 'total' => '358.40'],
            ['supplier_index' => 2, 'date_offset' => -3,  'code_seq' => '000003', 'status' => 'draft',  'subtotal' => '80.00',  'tax_base' => '80.00',  'tax_amount' => '9.60',  'total' => '89.60'],
        ];

        $created = 0;

        foreach ($definitions as $def) {
            $supplier = $suppliers->get($def['supplier_index']) ?? $suppliers->first();
            $isIssued = $def['status'] === 'issued';

            $settlement = PurchaseSettlement::create([
                'company_id' => $companyId,
                'code' => "LC-{$year}-{$def['code_seq']}",
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->legal_name,
                'supplier_identification_type' => $supplier->identification_type,
                'supplier_identification' => $supplier->identification_number,
                'supplier_address' => $supplier->tax_address,
                'establishment_code' => $isIssued ? $establishmentCode : null,
                'emission_point_code' => $isIssued ? $emissionPointCode : null,
                'sequential_number' => $isIssued ? mb_str_pad($def['code_seq'], 9, '0', STR_PAD_LEFT) : null,
                'currency_code' => 'USD',
                'status' => $def['status'],
                'issue_date' => now()->addDays($def['date_offset'])->toDateString(),
                'subtotal' => $def['subtotal'],
                'tax_base' => $def['tax_base'],
                'tax_amount' => $def['tax_amount'],
                'total' => $def['total'],
                'sri_payments' => $isIssued ? [['payment_method' => '20', 'total' => $def['total'], 'term' => 0, 'time_unit' => 'days']] : null,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            PurchaseSettlementItem::create([
                'purchase_settlement_id' => $settlement->getKey(),
                'product_code' => 'SERV-001',
                'product_name' => 'Servicio profesional - liquidación de compra',
                'sort_order' => 1,
                'quantity' => '1.000000',
                'unit_price' => $def['subtotal'],
                'discount_amount' => '0.00',
                'subtotal' => $def['subtotal'],
                'tax_amount' => $def['tax_amount'],
                'total' => $def['total'],
            ]);

            $created++;
        }

        $this->reportCreated($created);
    }
}
