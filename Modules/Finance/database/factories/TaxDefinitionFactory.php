<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Enums\TaxBaseTypeEnum;
use Modules\System\Enums\TaxCalculationTypeEnum;
use Modules\System\Enums\TaxGroupEnum;
use Modules\System\Enums\TaxNatureEnum;
use Modules\System\Models\TaxDefinition;

/**
 * @extends Factory<TaxDefinition>
 */
final class TaxDefinitionFactory extends Factory
{
    protected $model = TaxDefinition::class;

    public function definition(): array
    {
        return [
            'code' => mb_strtoupper(fake()->unique()->lexify('TAX_???')),
            'name' => fake()->words(3, true),
            'description' => null,
            'tax_group' => TaxGroupEnum::Iva->value,
            'tax_type' => TaxNatureEnum::Impuesto->value,
            'applies_to' => TaxAppliesToEnum::Ambos->value,
            'rate' => fake()->randomFloat(4, 0, 25),
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
        ];
    }
}
