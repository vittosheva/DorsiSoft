<?php

declare(strict_types=1);

namespace Modules\System\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\System\Models\DocumentType;

/**
 * @extends Factory<DocumentType>
 */
final class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('DOC_???'),
            'name' => fake()->words(2, true),
            'sri_code' => null,
            'generates_receivable' => false,
            'generates_payable' => false,
            'affects_inventory' => false,
            'affects_accounting' => false,
            'requires_authorization' => false,
            'allows_credit' => false,
            'is_electronic' => false,
            'is_purchase' => false,
            'default_debit_account_code' => null,
            'default_credit_account_code' => null,
            'behavior_flags' => null,
            'is_active' => true,
        ];
    }

    public function electronic(): static
    {
        return $this->state(['is_electronic' => true, 'requires_authorization' => true]);
    }

    public function affectingAccounting(): static
    {
        return $this->state(['affects_accounting' => true]);
    }
}
