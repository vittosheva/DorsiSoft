<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\InventoryDocumentType;

/**
 * @extends Factory<InventoryDocumentType>
 */
final class InventoryDocumentTypeFactory extends Factory
{
    protected $model = InventoryDocumentType::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('DOC-###'),
            'name' => fake()->words(2, true),
            'movement_type' => fake()->randomElement(['in', 'out', 'transfer', 'adjustment']),
            'affects_inventory' => true,
            'requires_source_document' => false,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function in(): static
    {
        return $this->state(['movement_type' => 'in']);
    }

    public function out(): static
    {
        return $this->state(['movement_type' => 'out']);
    }

    public function transfer(): static
    {
        return $this->state(['movement_type' => 'transfer']);
    }
}
