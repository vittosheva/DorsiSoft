<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Enums\AccountTypeEnum;
use Modules\Accounting\Models\ChartOfAccount;

/**
 * @extends Factory<ChartOfAccount>
 */
final class ChartOfAccountFactory extends Factory
{
    protected $model = ChartOfAccount::class;

    public function definition(): array
    {
        $type = fake()->randomElement(AccountTypeEnum::cases());

        return [
            'parent_id' => null,
            'code' => fake()->unique()->numerify('#.#.##'),
            'name' => fake()->words(3, true),
            'type' => $type->value,
            'nature' => $type->normalNature()->value,
            'level' => 2,
            'is_control' => false,
            'allows_entries' => true,
            'is_active' => true,
            'sri_code' => null,
            'notes' => null,
        ];
    }
}
