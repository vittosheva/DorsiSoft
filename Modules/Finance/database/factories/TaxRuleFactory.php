<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Models\TaxDefinition;
use Modules\System\Models\TaxRule;

/**
 * @extends Factory<TaxRule>
 */
final class TaxRuleFactory extends Factory
{
    protected $model = TaxRule::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(4),
            'description' => null,
            'applies_to' => TaxAppliesToEnum::Venta->value,
            'priority' => fake()->numberBetween(10, 200),
            'conditions' => null,
            'tax_definition_id' => TaxDefinition::factory(),
            'is_active' => true,
            'valid_from' => '2024-01-01',
            'valid_to' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
