<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Models\TaxDefinition;
use Modules\System\Models\TaxRule;

/**
 * ICE — Matrices de cálculo por categoría de producto
 * Fuente: Resolución NAC-DGERCGC / LRTI Art. 82
 */
final class IceTaxRuleLineSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $total = 0;

        $total += $this->seedVehiculos();
        $total += $this->seedAlcohol();
        $total += $this->seedCerveza();

        $this->reportCreated($total);
    }

    private function seedVehiculos(): int
    {
        $definition = TaxDefinition::query()->where('code', 'ICE_VEHICULOS')->first();

        if ($definition === null) {
            return 0;
        }

        $rule = TaxRule::updateOrCreate(
            ['name' => 'ICE Vehículos — Tabla progresiva por precio'],
            [
                'description' => 'ICE vehículos de transporte terrestre — tarifa progresiva según precio de venta al público',
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'priority' => 20,
                'conditions' => [
                    ['field' => 'product.ice_category', 'operator' => '=', 'value' => 'vehiculos'],
                ],
                'tax_definition_id' => $definition->id,
                'is_active' => true,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
            ]
        );

        DB::table('fin_tax_rule_lines')->where('tax_rule_id', $rule->id)->delete();

        $brackets = [
            ['sort_order' => 1, 'from_amount' => 0.00,       'to_amount' => 20000.00,  'rate' => 0.00,  'fixed_amount' => 0, 'excess_from' => 0, 'description' => 'Hasta $20.000 — 0%'],
            ['sort_order' => 2, 'from_amount' => 20000.00,   'to_amount' => 30000.00,  'rate' => 5.00,  'fixed_amount' => 0, 'excess_from' => 0, 'description' => '$20.001 a $30.000 — 5%'],
            ['sort_order' => 3, 'from_amount' => 30000.00,   'to_amount' => 40000.00,  'rate' => 10.00, 'fixed_amount' => 0, 'excess_from' => 0, 'description' => '$30.001 a $40.000 — 10%'],
            ['sort_order' => 4, 'from_amount' => 40000.00,   'to_amount' => 50000.00,  'rate' => 15.00, 'fixed_amount' => 0, 'excess_from' => 0, 'description' => '$40.001 a $50.000 — 15%'],
            ['sort_order' => 5, 'from_amount' => 50000.00,   'to_amount' => 70000.00,  'rate' => 20.00, 'fixed_amount' => 0, 'excess_from' => 0, 'description' => '$50.001 a $70.000 — 20%'],
            ['sort_order' => 6, 'from_amount' => 70000.00,   'to_amount' => null,      'rate' => 35.00, 'fixed_amount' => 0, 'excess_from' => 0, 'description' => 'Más de $70.000 — 35%'],
        ];

        return $this->insertLines($rule->id, $brackets);
    }

    private function seedAlcohol(): int
    {
        $definition = TaxDefinition::query()->where('code', 'ICE_ALCOHOL')->first();

        if ($definition === null) {
            return 0;
        }

        $rule = TaxRule::updateOrCreate(
            ['name' => 'ICE Alcohol — Tarifa específica por litro de alcohol puro'],
            [
                'description' => 'ICE bebidas alcohólicas — $7.68 por litro de alcohol puro + 75% del precio ex-fábrica',
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'priority' => 20,
                'conditions' => [
                    ['field' => 'product.ice_category', 'operator' => '=', 'value' => 'alcohol'],
                ],
                'tax_definition_id' => $definition->id,
                'is_active' => true,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
            ]
        );

        DB::table('fin_tax_rule_lines')->where('tax_rule_id', $rule->id)->delete();

        $lines = [
            ['sort_order' => 1, 'from_amount' => null, 'to_amount' => null, 'rate' => 75.00, 'fixed_amount' => 7.6800, 'excess_from' => 0, 'description' => 'Mixto: $7.68/litro alcohol puro + 75% PVP'],
        ];

        return $this->insertLines($rule->id, $lines);
    }

    private function seedCerveza(): int
    {
        $definition = TaxDefinition::query()->where('code', 'ICE_CERVEZA')->first();

        if ($definition === null) {
            return 0;
        }

        $rule = TaxRule::updateOrCreate(
            ['name' => 'ICE Cerveza — Tarifa mixta específica + porcentual'],
            [
                'description' => 'ICE cerveza — $0.91/litro específico + 75% sobre precio ex-fábrica',
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'priority' => 20,
                'conditions' => [
                    ['field' => 'product.ice_category', 'operator' => '=', 'value' => 'cerveza'],
                ],
                'tax_definition_id' => $definition->id,
                'is_active' => true,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
            ]
        );

        DB::table('fin_tax_rule_lines')->where('tax_rule_id', $rule->id)->delete();

        $lines = [
            ['sort_order' => 1, 'from_amount' => null, 'to_amount' => null, 'rate' => 75.00, 'fixed_amount' => 0.9100, 'excess_from' => 0, 'description' => 'Mixto: $0.91/litro + 75% precio ex-fábrica'],
        ];

        return $this->insertLines($rule->id, $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function insertLines(int $ruleId, array $lines): int
    {
        $now = now();
        $rows = array_map(fn (array $l): array => array_merge($l, [
            'tax_rule_id' => $ruleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $lines);

        DB::table('fin_tax_rule_lines')->insert($rows);

        return count($rows);
    }
}
