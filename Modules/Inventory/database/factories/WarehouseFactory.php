<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\Warehouse;

/**
 * @extends Factory<Warehouse>
 */
final class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'establishment_id' => null,
            'code' => 'BOD'.fake()->unique()->numberBetween(100, 999),
            'name' => fake()->words(2, true).' Warehouse',
            'address' => fake()->address(),
            'is_default' => false,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
