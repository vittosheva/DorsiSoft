<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Enums\TaxBaseTypeEnum;
use Modules\System\Enums\TaxCalculationTypeEnum;
use Modules\System\Enums\TaxGroupEnum;
use Modules\System\Enums\TaxNatureEnum;
use Modules\System\Models\TaxCatalog;
use Modules\System\Models\TaxDefinition;

final class TaxDefinitionSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $catalogIds = TaxCatalog::query()
            ->whereIn('code', ['IVA', 'ICE', 'IR', 'ISD'])
            ->pluck('id', 'code');

        $ivaId = $catalogIds['IVA'] ?? null;
        $iceId = $catalogIds['ICE'] ?? null;
        $irId = $catalogIds['IR'] ?? null;
        $isdId = $catalogIds['ISD'] ?? null;

        $definitions = [
            // =========================================
            // IVA — VIGENTES
            // =========================================
            [
                'code' => 'IVA_15',
                'name' => 'IVA 15%',
                'description' => 'Impuesto al Valor Agregado — tarifa general vigente 15%',
                'tax_group' => TaxGroupEnum::Iva->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Ambos->value,
                'rate' => 15.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '2',
                'sri_percentage_code' => '4',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'IVA_5',
                'name' => 'IVA 5%',
                'description' => 'IVA tarifa 5% para materiales de construcción y otros',
                'tax_group' => TaxGroupEnum::Iva->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Ambos->value,
                'rate' => 5.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '2',
                'sri_percentage_code' => '8',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'IVA_0',
                'name' => 'IVA 0%',
                'description' => 'IVA tarifa 0% — bienes y servicios gravados con tarifa cero',
                'tax_group' => TaxGroupEnum::Iva->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Ambos->value,
                'rate' => 0.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => true,
                'is_withholding' => false,
                'sri_code' => '2',
                'sri_percentage_code' => '0',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'IVA_EX',
                'name' => 'Exento de IVA',
                'description' => 'Transacción exenta de IVA según normativa',
                'tax_group' => TaxGroupEnum::Iva->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Ambos->value,
                'rate' => 0.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => true,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '2',
                'sri_percentage_code' => '10',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'IVA_NO',
                'name' => 'No objeto de IVA',
                'description' => 'Transacción fuera del objeto del IVA',
                'tax_group' => TaxGroupEnum::Iva->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Ambos->value,
                'rate' => 0.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '2',
                'sri_percentage_code' => '7',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],

            // =========================================
            // IVA — LEGACY
            // =========================================
            [
                'code' => 'IVA_12',
                'name' => 'IVA 12% (Legacy)',
                'description' => 'IVA tarifa 12% — histórico, no vigente desde 2024',
                'tax_group' => TaxGroupEnum::Iva->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Ambos->value,
                'rate' => 12.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '2',
                'sri_percentage_code' => '2',
                'valid_from' => '2014-01-01',
                'valid_to' => '2023-12-31',
                'is_active' => false,
            ],

            // =========================================
            // ICE
            // =========================================
            [
                'code' => 'ICE',
                'name' => 'ICE',
                'description' => 'Impuesto a los Consumos Especiales — configurable por producto',
                'tax_group' => TaxGroupEnum::Ice->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'rate' => null,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '3',
                'sri_percentage_code' => '0',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],

            // =========================================
            // ISD
            // =========================================
            [
                'code' => 'ISD',
                'name' => 'ISD',
                'description' => 'Impuesto a la Salida de Divisas',
                'tax_group' => TaxGroupEnum::Isd->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Ambos->value,
                'rate' => null,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '6',
                'sri_percentage_code' => null,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],

            // =========================================
            // RENTA — RETENCIONES
            // =========================================
            [
                'code' => 'IR_BIEN',
                'name' => 'Retención IR — Bienes',
                'description' => 'Retención en la fuente del Impuesto a la Renta para bienes',
                'tax_group' => TaxGroupEnum::Renta->value,
                'tax_type' => TaxNatureEnum::Retencion->value,
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'rate' => null,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => true,
                'sri_code' => '1',
                'sri_percentage_code' => null,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'IR_SERV',
                'name' => 'Retención IR — Servicios',
                'description' => 'Retención en la fuente del Impuesto a la Renta para servicios',
                'tax_group' => TaxGroupEnum::Renta->value,
                'tax_type' => TaxNatureEnum::Retencion->value,
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'rate' => null,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => true,
                'sri_code' => '1',
                'sri_percentage_code' => null,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],

            // =========================================
            // ICE — CATEGORÍAS ESPECÍFICAS
            // =========================================
            [
                'code' => 'ICE_ALCOHOL',
                'name' => 'ICE — Bebidas Alcohólicas (específico)',
                'description' => 'ICE bebidas alcohólicas — tarifa específica por litro de alcohol puro según NAC-DGERCGC',
                'tax_group' => TaxGroupEnum::Ice->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'rate' => 0.0000,
                'fixed_amount' => 7.6800,
                'calculation_type' => TaxCalculationTypeEnum::Fixed->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '3',
                'sri_percentage_code' => '3051',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'ICE_CERVEZA',
                'name' => 'ICE — Cerveza (mixto)',
                'description' => 'ICE cerveza — tarifa mixta: específica $0.91/litro + porcentaje sobre PVP',
                'tax_group' => TaxGroupEnum::Ice->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'rate' => 75.0000,
                'fixed_amount' => 0.9100,
                'calculation_type' => TaxCalculationTypeEnum::Mixed->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '3',
                'sri_percentage_code' => '3011',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'ICE_CIGARRILLOS',
                'name' => 'ICE — Cigarrillos (específico)',
                'description' => 'ICE cigarrillos — tarifa específica por cajetilla de 20 unidades',
                'tax_group' => TaxGroupEnum::Ice->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'rate' => 0.0000,
                'fixed_amount' => 0.1640,
                'calculation_type' => TaxCalculationTypeEnum::Fixed->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '3',
                'sri_percentage_code' => '3071',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'ICE_BEBIDAS',
                'name' => 'ICE — Bebidas Azucaradas (%)',
                'description' => 'ICE bebidas con azúcar añadida — tarifa porcentual sobre PVP',
                'tax_group' => TaxGroupEnum::Ice->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'rate' => 10.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '3',
                'sri_percentage_code' => '3191',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'ICE_VEHICULOS',
                'name' => 'ICE — Vehículos (%)',
                'description' => 'ICE vehículos de transporte terrestre — tarifa porcentual progresiva según precio',
                'tax_group' => TaxGroupEnum::Ice->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'rate' => 5.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '3',
                'sri_percentage_code' => '3151',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'ICE_PERFUMES',
                'name' => 'ICE — Perfumes y Fragancias (%)',
                'description' => 'ICE perfumes, fragancias y preparaciones de tocador — 20% sobre PVP',
                'tax_group' => TaxGroupEnum::Ice->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'rate' => 20.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '3',
                'sri_percentage_code' => '3101',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'ICE_VIDEOJUEGOS',
                'name' => 'ICE — Videojuegos (%)',
                'description' => 'ICE videojuegos — 35% sobre PVP',
                'tax_group' => TaxGroupEnum::Ice->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'rate' => 35.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '3',
                'sri_percentage_code' => '3131',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'ICE_TELECOM',
                'name' => 'ICE — Telecomunicaciones y Radiodifusión (%)',
                'description' => 'ICE servicios de telecomunicaciones y radiodifusión — 15% sobre precio del servicio',
                'tax_group' => TaxGroupEnum::Ice->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'rate' => 15.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '3',
                'sri_percentage_code' => '3211',
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],

            // =========================================
            // IR — IMPUESTO A LA RENTA
            // =========================================
            [
                'code' => 'IR_PN_PROGRESIVO',
                'name' => 'IR — Personas Naturales (progresivo)',
                'description' => 'Impuesto a la Renta personas naturales — tabla progresiva SRI vigente 2024',
                'tax_group' => TaxGroupEnum::Renta->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'rate' => 0.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '1',
                'sri_percentage_code' => null,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'IR_SOCIEDADES',
                'name' => 'IR — Sociedades 25%',
                'description' => 'Impuesto a la Renta para sociedades — tarifa fija 25%',
                'tax_group' => TaxGroupEnum::Renta->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'rate' => 25.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '1',
                'sri_percentage_code' => null,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'IR_RIMPE_POPULAR',
                'name' => 'IR — RIMPE Popular 1%',
                'description' => 'Impuesto a la Renta régimen RIMPE Negocios Populares — tarifa 1% sobre ingresos brutos',
                'tax_group' => TaxGroupEnum::Renta->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'rate' => 1.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '1',
                'sri_percentage_code' => null,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'IR_RIMPE_EMPRENDEDOR',
                'name' => 'IR — RIMPE Emprendedor 2%',
                'description' => 'Impuesto a la Renta régimen RIMPE Emprendedores — tarifa 2% sobre ingresos brutos',
                'tax_group' => TaxGroupEnum::Renta->value,
                'tax_type' => TaxNatureEnum::Impuesto->value,
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'rate' => 2.0000,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => false,
                'sri_code' => '1',
                'sri_percentage_code' => null,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],

            // =========================================
            // IVA — RETENCIONES
            // =========================================
            [
                'code' => 'RET_IVA',
                'name' => 'Retención IVA',
                'description' => 'Retención del Impuesto al Valor Agregado',
                'tax_group' => TaxGroupEnum::Iva->value,
                'tax_type' => TaxNatureEnum::Retencion->value,
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'rate' => null,
                'fixed_amount' => null,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'base_type' => TaxBaseTypeEnum::Precio->value,
                'is_exempt' => false,
                'is_zero_rate' => false,
                'is_withholding' => true,
                'sri_code' => '2',
                'sri_percentage_code' => null,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'is_active' => true,
            ],
        ];

        $created = 0;
        $updated = 0;

        $groupToCatalogId = [
            TaxGroupEnum::Iva->value => $ivaId,
            TaxGroupEnum::Ice->value => $iceId,
            TaxGroupEnum::Renta->value => $irId,
            TaxGroupEnum::Isd->value => $isdId,
        ];

        foreach ($definitions as $data) {
            $data['tax_catalog_id'] = $groupToCatalogId[$data['tax_group']] ?? null;

            $existing = TaxDefinition::query()
                ->where('code', $data['code'])
                ->value('id');

            if ($existing !== null) {
                TaxDefinition::query()
                    ->where('id', $existing)
                    ->update(array_merge($data, ['updated_at' => now()]));

                $updated++;
            } else {
                TaxDefinition::create(array_merge($data, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));

                $created++;
            }
        }

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
