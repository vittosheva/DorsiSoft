<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Finance\Enums\TaxTypeEnum;
use Modules\Finance\Models\Tax;
use Modules\System\Enums\TaxCalculationTypeEnum;
use Modules\System\Models\TaxDefinition;

final class TaxSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed demo taxes for the first registered company.
     *
     * Uses Ecuador's tax structure (IVA, ICE) as reference context.
     * Code is generated manually via DB::table() because WithoutModelEvents
     * in DatabaseSeeder suppresses the creating event used by HasAutoCode.
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('TaxSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        $taxes = [
            // ===============================
            // ✅ IVA VIGENTES
            // ===============================

            [
                'name' => 'IVA 15%',
                'type' => TaxTypeEnum::Iva->value,
                'sri_code' => 2,
                'sri_percentage_code' => 4,
                'rate' => 15.0000,
                'tax_category' => 'taxable',
                'tax_catalog_version' => '2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'Impuesto al Valor Agregado — tarifa general vigente 15%',
                'is_default' => true,
                'is_active' => true,
                'legacy_names' => ['IVA 12%'],
            ],

            [
                'name' => 'IVA 5%',
                'type' => TaxTypeEnum::Iva->value,
                'sri_code' => 2,
                'sri_percentage_code' => 8,
                'rate' => 5.0000,
                'tax_category' => 'taxable',
                'tax_catalog_version' => '2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'IVA tarifa 5% para materiales de construcción',
                'is_default' => false,
                'is_active' => true,
                'legacy_names' => ['IVA reducido'],
            ],

            [
                'name' => 'IVA 0%',
                'type' => TaxTypeEnum::Iva->value,
                'sri_code' => 2,
                'sri_percentage_code' => 0,
                'rate' => 0.0000,
                'tax_category' => 'taxable', // 👈 GRAVADO 0%
                'tax_catalog_version' => '2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'IVA tarifa 0% (bienes y servicios gravados con tarifa cero)',
                'is_default' => false,
                'is_active' => true,
                'legacy_names' => [],
            ],

            [
                'name' => 'Exento de IVA',
                'type' => TaxTypeEnum::Iva->value,
                'sri_code' => 2,
                'sri_percentage_code' => 10,
                'rate' => 0.0000,
                'tax_category' => 'exempt',
                'tax_catalog_version' => '2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'Transacción exenta de IVA según normativa',
                'is_default' => false,
                'is_active' => true,
                'legacy_names' => [],
            ],

            [
                'name' => 'No objeto de IVA',
                'type' => TaxTypeEnum::Iva->value,
                'sri_code' => 2,
                'sri_percentage_code' => 7,
                'rate' => 0.0000,
                'tax_category' => 'non_object',
                'tax_catalog_version' => '2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'Transacción fuera del objeto del IVA',
                'is_default' => false,
                'is_active' => true,
                'legacy_names' => [],
            ],

            // ===============================
            // ⚠️ IVA LEGACY / COMPATIBILIDAD
            // ===============================

            [
                'name' => 'IVA 12% (Legacy)',
                'type' => TaxTypeEnum::Iva->value,
                'sri_code' => 2,
                'sri_percentage_code' => 2,
                'rate' => 12.0000,
                'tax_category' => 'taxable',
                'tax_catalog_version' => 'pre-2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'IVA tarifa 12% (histórico, no vigente)',
                'is_default' => false,
                'is_active' => false,
                'legacy_names' => [],
            ],

            [
                'name' => 'IVA 5% (Legacy código)',
                'type' => TaxTypeEnum::Iva->value,
                'sri_code' => 2,
                'sri_percentage_code' => 5,
                'rate' => 5.0000,
                'tax_category' => 'taxable',
                'tax_catalog_version' => 'pre-2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'IVA 5% con código antiguo SRI',
                'is_default' => false,
                'is_active' => false,
                'legacy_names' => [],
            ],

            [
                'name' => 'IVA 0% (Variante catálogo)',
                'type' => TaxTypeEnum::Iva->value,
                'sri_code' => 2,
                'sri_percentage_code' => 6,
                'rate' => 0.0000,
                'tax_category' => 'taxable',
                'tax_catalog_version' => 'pre-2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'IVA 0% con código alternativo SRI',
                'is_default' => false,
                'is_active' => false,
                'legacy_names' => [],
            ],

            // ===============================
            // ⚠️ CASOS ESPECIALES
            // ===============================

            [
                'name' => 'IVA diferenciado',
                'type' => TaxTypeEnum::Iva->value,
                'sri_code' => 2,
                'sri_percentage_code' => 9,
                'rate' => 0.0000,
                'tax_category' => 'special',
                'tax_catalog_version' => '2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'IVA con tratamiento especial según normativa específica',
                'is_default' => false,
                'is_active' => false,
                'legacy_names' => [],
            ],

            [
                'name' => 'IVA no soportado',
                'type' => TaxTypeEnum::Iva->value,
                'sri_code' => 2,
                'sri_percentage_code' => 11,
                'rate' => 0.0000,
                'tax_category' => 'special',
                'tax_catalog_version' => '2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'IVA sin derecho a crédito tributario',
                'is_default' => false,
                'is_active' => false,
                'legacy_names' => [],
            ],

            // ===============================
            // 🔹 OTROS IMPUESTOS
            // ===============================

            [
                'name' => 'ICE',
                'type' => TaxTypeEnum::Ice->value,
                'sri_code' => 3,
                'sri_percentage_code' => 0,
                'rate' => 0.0000,
                'tax_category' => 'special',
                'tax_catalog_version' => '2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'Impuesto a los Consumos Especiales (configurable por producto)',
                'is_default' => false,
                'is_active' => false,
                'legacy_names' => [],
            ],

            [
                'name' => 'ISD',
                'type' => TaxTypeEnum::Isd->value,
                'sri_code' => 5,
                'sri_percentage_code' => 0,
                'rate' => 0.0000,
                'tax_category' => 'special',
                'tax_catalog_version' => '2024',
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'description' => 'Impuesto a la Salida de Divisas',
                'is_default' => false,
                'is_active' => false,
                'legacy_names' => [],
            ],
        ];

        // Determine next sequential code from the current max
        $maxCode = DB::table('fin_taxes')
            ->where('company_id', $companyId)
            ->where('code', 'LIKE', 'IMP%')
            ->max('code');

        $seq = $maxCode ? ((int) mb_substr($maxCode, 3)) + 1 : 1;

        $created = 0;
        $updated = 0;

        foreach ($taxes as $data) {
            $lookupNames = [$data['name'], ...$data['legacy_names']];

            $existingId = DB::table('fin_taxes')
                ->where('company_id', $companyId)
                ->where('type', $data['type'])
                ->where('sri_code', $data['sri_code'])
                ->where('sri_percentage_code', $data['sri_percentage_code'])
                ->whereNull('deleted_at')
                ->value('id');

            $payload = collect($data)
                ->except('legacy_names')
                ->all();

            $definitionId = TaxDefinition::query()
                ->where('sri_code', $data['sri_code'])
                ->where('sri_percentage_code', $data['sri_percentage_code'])
                ->value('id');

            if ($existingId !== null) {
                DB::table('fin_taxes')
                    ->where('id', $existingId)
                    ->update(array_merge($payload, [
                        'tax_definition_id' => $definitionId,
                        'updated_by' => 1,
                        'updated_at' => now(),
                    ]));

                $updated++;

                continue;
            }

            $code = 'IMP'.mb_str_pad((string) $seq++, 3, '0', STR_PAD_LEFT);

            Tax::create(array_merge($payload, [
                'company_id' => $companyId,
                'tax_definition_id' => $definitionId,
                'code' => $code,
                'created_by' => 1,
                'updated_by' => 1,
            ]));

            $created++;
        }

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
