<?php

declare(strict_types=1);

namespace Modules\Sri\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sri\Enums\SriCatalogTypeEnum;
use Modules\System\Models\SriCatalog;

/**
 * @extends Factory<SriCatalog>
 */
final class SriCatalogFactory extends Factory
{
    protected $model = SriCatalog::class;

    public function definition(): array
    {
        return [
            'catalog_type' => fake()->randomElement(SriCatalogTypeEnum::cases())->value,
            'code' => mb_strtoupper(fake()->unique()->lexify('??')),
            'name' => fake()->words(3, true),
            'description' => null,
            'extra_data' => null,
            'is_active' => true,
            'valid_from' => null,
            'valid_to' => null,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
