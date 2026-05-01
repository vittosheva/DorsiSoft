<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Models\TaxDefinition;
use Modules\System\Models\TaxRule;

final class TaxRuleSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $definitions = TaxDefinition::query()
            ->whereIn('code', ['IVA_15', 'IVA_5', 'IVA_0', 'IVA_EX', 'IVA_NO', 'IR_BIEN', 'IR_SERV', 'RET_IVA'])
            ->pluck('id', 'code');

        if ($definitions->isEmpty()) {
            $this->command?->warn('TaxRuleSeeder: No tax definitions found. Run TaxDefinitionSeeder first.');

            return;
        }

        $rules = [
            // ─── IVA — VENTAS ───────────────────────────────────────────────
            [
                'name' => 'IVA 15% — Regla general ventas',
                'description' => 'Aplica IVA 15% a todas las ventas que no tengan una regla más específica.',
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'priority' => 100,
                'conditions' => null,
                'definition_code' => 'IVA_15',
                'valid_from' => '2024-01-01',
            ],
            [
                'name' => 'IVA 0% — Bienes básicos',
                'description' => 'Aplica IVA 0% a productos con categoría basic_goods.',
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'priority' => 50,
                'conditions' => [
                    ['field' => 'product.category', 'operator' => '=', 'value' => 'basic_goods'],
                ],
                'definition_code' => 'IVA_0',
                'valid_from' => '2024-01-01',
            ],
            [
                'name' => 'Exento de IVA — Productos exentos',
                'description' => 'Aplica exención de IVA a productos con categoría exempt.',
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'priority' => 50,
                'conditions' => [
                    ['field' => 'product.category', 'operator' => '=', 'value' => 'exempt'],
                ],
                'definition_code' => 'IVA_EX',
                'valid_from' => '2024-01-01',
            ],
            [
                'name' => 'No objeto de IVA — Productos no objeto',
                'description' => 'Aplica no objeto de IVA a productos con categoría non_object.',
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'priority' => 50,
                'conditions' => [
                    ['field' => 'product.category', 'operator' => '=', 'value' => 'non_object'],
                ],
                'definition_code' => 'IVA_NO',
                'valid_from' => '2024-01-01',
            ],
            [
                'name' => 'IVA 5% — Materiales de construcción y otros',
                'description' => 'Aplica IVA 5% a productos con categoría construction_materials.',
                'applies_to' => TaxAppliesToEnum::Venta->value,
                'priority' => 50,
                'conditions' => [
                    ['field' => 'product.category', 'operator' => '=', 'value' => 'construction_materials'],
                ],
                'definition_code' => 'IVA_5',
                'valid_from' => '2024-01-01',
            ],

            // ─── RETENCIONES — COMPRAS ───────────────────────────────────────
            [
                'name' => 'Retención IR Bienes — Proveedor RUC',
                'description' => 'Aplica retención IR por compra de bienes a proveedores con RUC.',
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'priority' => 60,
                'conditions' => [
                    ['field' => 'partner.identification_type', 'operator' => '=', 'value' => '04'],
                    ['field' => 'product.category', 'operator' => 'in', 'value' => ['goods', 'product']],
                ],
                'definition_code' => 'IR_BIEN',
                'valid_from' => '2024-01-01',
            ],
            [
                'name' => 'Retención IR Servicios — Proveedor RUC',
                'description' => 'Aplica retención IR por compra de servicios a proveedores con RUC.',
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'priority' => 60,
                'conditions' => [
                    ['field' => 'partner.identification_type', 'operator' => '=', 'value' => '04'],
                    ['field' => 'product.category', 'operator' => 'in', 'value' => ['services', 'service']],
                ],
                'definition_code' => 'IR_SERV',
                'valid_from' => '2024-01-01',
            ],
            [
                'name' => 'Retención IVA 30% — Bienes',
                'description' => 'Aplica retención IVA 30% en compras de bienes a proveedores RUC.',
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'priority' => 70,
                'conditions' => [
                    ['field' => 'partner.identification_type', 'operator' => '=', 'value' => '04'],
                    ['field' => 'product.category', 'operator' => 'in', 'value' => ['goods', 'product']],
                ],
                'definition_code' => 'RET_IVA',
                'valid_from' => '2024-01-01',
            ],
            [
                'name' => 'Retención IVA 70% — Servicios',
                'description' => 'Aplica retención IVA 70% en compras de servicios a proveedores RUC.',
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'priority' => 70,
                'conditions' => [
                    ['field' => 'partner.identification_type', 'operator' => '=', 'value' => '04'],
                    ['field' => 'product.category', 'operator' => 'in', 'value' => ['services', 'service']],
                ],
                'definition_code' => 'RET_IVA',
                'valid_from' => '2024-01-01',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($rules as $data) {
            $definitionCode = $data['definition_code'];

            if (! isset($definitions[$definitionCode])) {
                continue;
            }

            $conditions = $data['conditions'] !== null ? json_encode($data['conditions']) : null;

            $model = TaxRule::query()
                ->updateOrCreate(
                    ['name' => $data['name']],
                    [
                        'description' => $data['description'],
                        'applies_to' => $data['applies_to'],
                        'priority' => $data['priority'],
                        'conditions' => $data['conditions'],
                        'tax_definition_id' => $definitions[$definitionCode],
                        'is_active' => true,
                        'valid_from' => $data['valid_from'],
                        'valid_to' => null,
                    ]
                );

            $this->tallyModelChange($model, $created, $updated);
        }

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
