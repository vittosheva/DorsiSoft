<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Enums\JournalEntryStatusEnum;
use Modules\Accounting\Models\JournalEntry;

/**
 * @extends Factory<JournalEntry>
 */
final class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'reference' => 'JE-'.fake()->year().'-'.fake()->unique()->numerify('######'),
            'description' => fake()->sentence(),
            'entry_date' => fake()->dateThisYear(),
            'status' => JournalEntryStatusEnum::Draft->value,
            'source_type' => null,
            'source_id' => null,
            'total_debit' => '0.0000',
            'total_credit' => '0.0000',
            'approved_at' => null,
            'approved_by' => null,
            'voided_at' => null,
            'voided_by' => null,
            'void_reason' => null,
            'reversed_by_entry_id' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => JournalEntryStatusEnum::Approved->value,
            'approved_at' => now(),
        ]);
    }
}
