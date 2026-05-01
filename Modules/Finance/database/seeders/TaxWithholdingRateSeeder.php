<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\System\Enums\WithholdingAppliesToEnum;
use Modules\System\Models\TaxDefinition;
use Modules\System\Models\TaxWithholdingRate;

final class TaxWithholdingRateSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $irBienId = TaxDefinition::where('code', 'IR_BIEN')->value('id');
        $irServId = TaxDefinition::where('code', 'IR_SERV')->value('id');
        $retIvaId = TaxDefinition::where('code', 'RET_IVA')->value('id');

        if (! $irBienId || ! $irServId || ! $retIvaId) {
            $this->command?->warn('TaxWithholdingRateSeeder: TaxDefinitions not found. Run TaxDefinitionSeeder first.');

            return;
        }

        $rates = [
            // =========================================
            // RETENCIONES IR — BIENES (código ATS)
            // =========================================
            ['tax_definition_id' => $irBienId, 'percentage' => 1.00, 'sri_code' => '303A', 'description' => 'Bienes — 1%', 'applies_to' => WithholdingAppliesToEnum::Bien->value],
            ['tax_definition_id' => $irBienId, 'percentage' => 1.75, 'sri_code' => '312', 'description' => 'Importaciones — 1.75%', 'applies_to' => WithholdingAppliesToEnum::Bien->value],
            ['tax_definition_id' => $irBienId, 'percentage' => 2.00, 'sri_code' => '303B', 'description' => 'Bienes — 2%', 'applies_to' => WithholdingAppliesToEnum::Bien->value],

            // =========================================
            // RETENCIONES IR — SERVICIOS (código ATS)
            // =========================================
            ['tax_definition_id' => $irServId, 'percentage' => 1.00, 'sri_code' => '307', 'description' => 'Servicios transporte — 1%', 'applies_to' => WithholdingAppliesToEnum::Servicio->value],
            ['tax_definition_id' => $irServId, 'percentage' => 2.00, 'sri_code' => '309', 'description' => 'Servicios — 2%', 'applies_to' => WithholdingAppliesToEnum::Servicio->value],
            ['tax_definition_id' => $irServId, 'percentage' => 8.00, 'sri_code' => '322', 'description' => 'Arrendamiento mercantil — 8%', 'applies_to' => WithholdingAppliesToEnum::Servicio->value],
            ['tax_definition_id' => $irServId, 'percentage' => 10.00, 'sri_code' => '304A', 'description' => 'Honorarios profesionales — 10%', 'applies_to' => WithholdingAppliesToEnum::Servicio->value],
            ['tax_definition_id' => $irServId, 'percentage' => 25.00, 'sri_code' => '332', 'description' => 'Paraísos fiscales — 25%', 'applies_to' => WithholdingAppliesToEnum::Servicio->value],

            // =========================================
            // RETENCIONES IVA (código ATS)
            // =========================================
            ['tax_definition_id' => $retIvaId, 'percentage' => 30.00, 'sri_code' => '721', 'description' => 'Retención IVA 30% — bienes', 'applies_to' => WithholdingAppliesToEnum::Bien->value],
            ['tax_definition_id' => $retIvaId, 'percentage' => 70.00, 'sri_code' => '723', 'description' => 'Retención IVA 70% — servicios', 'applies_to' => WithholdingAppliesToEnum::Servicio->value],
            ['tax_definition_id' => $retIvaId, 'percentage' => 100.00, 'sri_code' => '725', 'description' => 'Retención IVA 100% — casos especiales', 'applies_to' => WithholdingAppliesToEnum::Ambos->value],
        ];

        $created = 0;
        $updated = 0;

        foreach ($rates as $data) {
            $existing = TaxWithholdingRate::query()
                ->where('tax_definition_id', $data['tax_definition_id'])
                ->where('sri_code', $data['sri_code'])
                ->value('id');

            if ($existing !== null) {
                TaxWithholdingRate::query()
                    ->where('id', $existing)
                    ->update(array_merge($data, ['updated_at' => now()]));

                $updated++;
            } else {
                TaxWithholdingRate::create(array_merge($data, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));

                $created++;
            }
        }

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
