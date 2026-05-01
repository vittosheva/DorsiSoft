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
 * IR Personas Naturales — Tabla Progresiva SRI Ecuador 2024
 * Fuente: Resolución NAC-DGERCGC24-00000001 / LRTI Art. 36
 */
final class IrBracketSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $definition = TaxDefinition::query()->where('code', 'IR_PN_PROGRESIVO')->first();

        if ($definition === null) {
            $this->command?->warn('IrBracketSeeder: Definition IR_PN_PROGRESIVO not found. Run TaxDefinitionSeeder first.');

            return;
        }

        $rule = TaxRule::updateOrCreate(
            ['name' => 'IR PN — Tabla progresiva 2024'],
            [
                'description' => 'Impuesto a la Renta personas naturales — tabla progresiva vigente 2024 (fracción básica + tarifa marginal)',
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'priority' => 10,
                'conditions' => [
                    ['field' => 'partner.taxpayer_type', 'operator' => '=', 'value' => 'persona_natural'],
                ],
                'tax_definition_id' => $definition->id,
                'is_active' => true,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
            ]
        );

        DB::table('fin_tax_rule_lines')->where('tax_rule_id', $rule->id)->delete();

        $brackets = [
            ['sort_order' => 1,  'from_amount' => 0.00,       'to_amount' => 11722.00,  'excess_from' => 0.00,       'rate' => 0.00,  'fixed_amount' => 0.00,   'description' => 'Fracción básica — tarifa 0%'],
            ['sort_order' => 2,  'from_amount' => 11722.00,   'to_amount' => 14931.00,  'excess_from' => 11722.00,   'rate' => 5.00,  'fixed_amount' => 0.00,   'description' => '5% sobre excedente de $11.722'],
            ['sort_order' => 3,  'from_amount' => 14931.00,   'to_amount' => 19919.00,  'excess_from' => 14931.00,   'rate' => 10.00, 'fixed_amount' => 160.45, 'description' => '10% sobre excedente de $14.931'],
            ['sort_order' => 4,  'from_amount' => 19919.00,   'to_amount' => 26031.00,  'excess_from' => 19919.00,   'rate' => 12.00, 'fixed_amount' => 659.25, 'description' => '12% sobre excedente de $19.919'],
            ['sort_order' => 5,  'from_amount' => 26031.00,   'to_amount' => 34255.00,  'excess_from' => 26031.00,   'rate' => 15.00, 'fixed_amount' => 1392.69, 'description' => '15% sobre excedente de $26.031'],
            ['sort_order' => 6,  'from_amount' => 34255.00,   'to_amount' => 45407.00,  'excess_from' => 34255.00,   'rate' => 20.00, 'fixed_amount' => 2626.29, 'description' => '20% sobre excedente de $34.255'],
            ['sort_order' => 7,  'from_amount' => 45407.00,   'to_amount' => 60450.00,  'excess_from' => 45407.00,   'rate' => 25.00, 'fixed_amount' => 4856.69, 'description' => '25% sobre excedente de $45.407'],
            ['sort_order' => 8,  'from_amount' => 60450.00,   'to_amount' => 80605.00,  'excess_from' => 60450.00,   'rate' => 30.00, 'fixed_amount' => 8617.44, 'description' => '30% sobre excedente de $60.450'],
            ['sort_order' => 9,  'from_amount' => 80605.00,   'to_amount' => null,      'excess_from' => 80605.00,   'rate' => 35.00, 'fixed_amount' => 14663.94, 'description' => '35% sobre excedente de $80.605'],
        ];

        $now = now();
        $lines = array_map(fn (array $b): array => array_merge($b, [
            'tax_rule_id' => $rule->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $brackets);

        DB::table('fin_tax_rule_lines')->insert($lines);

        $this->reportCreated(count($lines));
    }
}
