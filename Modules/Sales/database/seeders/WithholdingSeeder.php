<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\Sales\Models\Withholding;
use Modules\Sales\Models\WithholdingItem;

final class WithholdingSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed 3 demo withholdings for the first registered company.
     *
     * Idempotency: if any withholding already exists for the company, the seeder
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
            $this->command->warn('WithholdingSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        $hasRecords = DB::table('sales_withholdings')
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

        $suppliers = DB::table('core_business_partners')
            ->select(['core_business_partners.id', 'core_business_partners.legal_name', 'core_business_partners.identification_number', 'core_business_partners.identification_type', 'core_business_partners.tax_address'])
            ->join('core_business_partner_role', 'core_business_partners.id', '=', 'core_business_partner_role.business_partner_id')
            ->where('core_business_partner_role.partner_role_id', $supplierRoleId)
            ->where('core_business_partners.company_id', $companyId)
            ->whereNull('core_business_partners.deleted_at')
            ->orderBy('core_business_partners.id')
            ->limit(3)
            ->get();

        if ($suppliers->isEmpty()) {
            $this->command->warn('WithholdingSeeder: No suppliers found. Skipping.');

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
            ['supplier_index' => 0, 'date_offset' => -30, 'code_seq' => '000001', 'status' => 'issued'],
            ['supplier_index' => 1, 'date_offset' => -15, 'code_seq' => '000002', 'status' => 'issued'],
            ['supplier_index' => 2, 'date_offset' => -5,  'code_seq' => '000003', 'status' => 'draft'],
        ];

        $created = 0;

        foreach ($definitions as $def) {
            $supplier = $suppliers->get($def['supplier_index']) ?? $suppliers->first();
            $date = now()->addDays($def['date_offset'])->toDateString();
            $isIssued = $def['status'] === 'issued';

            $withholding = Withholding::create([
                'company_id' => $companyId,
                'code' => "RET-{$year}-{$def['code_seq']}",
                'business_partner_id' => $supplier->id,
                'supplier_name' => $supplier->legal_name,
                'supplier_identification_type' => $supplier->identification_type,
                'supplier_identification' => $supplier->identification_number,
                'supplier_address' => $supplier->tax_address,
                'establishment_code' => $isIssued ? $establishmentCode : null,
                'emission_point_code' => $isIssued ? $emissionPointCode : null,
                'sequential_number' => $isIssued ? mb_str_pad($def['code_seq'], 9, '0', STR_PAD_LEFT) : null,
                'status' => $def['status'],
                'period_fiscal' => now()->addDays($def['date_offset'])->format('m/Y'),
                'issue_date' => $date,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // IVA withholding item (30% of IVA base)
            WithholdingItem::create([
                'withholding_id' => $withholding->getKey(),
                'tax_type' => '2', // IVA
                'tax_code' => '10', // 30% IVA
                'base_amount' => '100.00',
                'tax_rate' => '30.00',
                'withheld_amount' => '36.00',
            ]);

            // Income tax withholding item (1% services)
            WithholdingItem::create([
                'withholding_id' => $withholding->getKey(),
                'tax_type' => '1', // Renta
                'tax_code' => '3440', // 1% servicios
                'base_amount' => '200.00',
                'tax_rate' => '1.00',
                'withheld_amount' => '2.00',
            ]);

            $created++;
        }

        $this->reportCreated($created);
    }
}
