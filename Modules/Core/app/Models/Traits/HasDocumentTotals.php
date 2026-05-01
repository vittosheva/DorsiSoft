<?php

declare(strict_types=1);

namespace Modules\Core\Models\Traits;

/**
 * Provides standard financial field casts for transactional document models.
 *
 * Include this trait in any document model that has the standard totals columns:
 * subtotal, tax_base, discount_amount, tax_amount, total.
 *
 * Usage: merge documentTotalCasts() into the model's casts() method.
 */
trait HasDocumentTotals
{
    /**
     * Standard decimal casts for document financial totals.
     * Merge these into the model's casts() return array.
     *
     * @return array<string, string>
     */
    protected function documentTotalCasts(): array
    {
        return [
            'subtotal' => 'decimal:4',
            'tax_base' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }
}
