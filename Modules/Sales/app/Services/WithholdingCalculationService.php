<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Sales\Models\Withholding;

final class WithholdingCalculationService
{
    public function calculateWithheld(float|string $base, float|string $rate): string
    {
        return number_format((float) $base * (float) $rate / 100, 2, '.', '');
    }

    /**
     * Propagate header-level source document fields to all withholding items.
     * Called after the Withholding record and its items are persisted.
     */
    public function propagateSourceDocument(Withholding $withholding): void
    {
        if (blank($withholding->source_document_number)) {
            return;
        }

        $withholding->items()->update([
            'source_document_type' => $withholding->source_document_type,
            'source_document_number' => $withholding->source_document_number,
            'source_document_date' => $withholding->source_document_date,
            'source_purchase_settlement_id' => $withholding->source_purchase_settlement_id,
        ]);
    }

    /**
     * @return array{total_withheld: string, item_count: int}
     */
    public function computeTotals(Withholding $withholding): array
    {
        $withholding->loadMissing('items');

        return [
            'total_withheld' => number_format($withholding->items->sum('withheld_amount'), 2, '.', ''),
            'item_count' => $withholding->items->count(),
        ];
    }

    /**
     * Validate that each item's withheld_amount matches base × rate / 100 (±0.01 tolerance).
     *
     * @param  array<int, array{base_amount: string|float, tax_rate: string|float, withheld_amount: string|float}>  $items
     */
    public function validateBalance(array $items): bool
    {
        foreach ($items as $item) {
            $expected = (float) $this->calculateWithheld($item['base_amount'] ?? 0, $item['tax_rate'] ?? 0);
            $actual = (float) ($item['withheld_amount'] ?? 0);

            if (abs($expected - $actual) > 0.01) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ensure no two items share the same withholding_rate_id.
     *
     * @param  array<int, array{withholding_rate_id?: int|string|null}>  $items
     */
    public function hasDuplicateRates(array $items): bool
    {
        $rateIds = array_filter(array_column($items, 'withholding_rate_id'));

        return count($rateIds) !== count(array_unique($rateIds));
    }
}
