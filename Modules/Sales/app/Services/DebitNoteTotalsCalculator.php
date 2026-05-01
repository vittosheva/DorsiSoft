<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Contracts\DocumentTotalsCalculator;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\DebitNoteItem;
use Modules\Sales\Services\Concerns\ComputesItemDiscount;

final class DebitNoteTotalsCalculator implements DocumentTotalsCalculator
{
    use ComputesItemDiscount;

    public function __construct(private readonly ItemTaxComputationService $itemTaxComputationService) {}

    public function recalculate(Model $document): void
    {
        /** @var DebitNote $debitNote */
        $debitNote = $document;
        $debitNote->loadMissing(['items.taxes']);

        $reasons = collect($debitNote->reasons ?? [])
            ->filter(static fn (mixed $reason): bool => is_array($reason))
            ->values();

        if ($reasons->isNotEmpty()) {
            $this->recalculateFromReasons($debitNote, $reasons->all());

            return;
        }

        $this->recalculateFromItems($debitNote);
    }

    public function recalculateItem(Model $item): void
    {
        /** @var DebitNoteItem $item */
        $gross = bcmul((string) $item->quantity, (string) $item->unit_price, 8);
        $discountAmount = $this->computeItemDiscount($item, $gross);
        $subtotal = bcsub($gross, $discountAmount, 4);

        $computation = $this->itemTaxComputationService->compute((string) $item->quantity, $subtotal, $item->taxes);

        foreach ($computation['taxes'] as $computedTax) {
            /** @var object $itemTax */
            $itemTax = $computedTax['source'];
            $itemTax->tax_type = $computedTax['tax_type'];
            $itemTax->tax_code = $computedTax['tax_code'];
            $itemTax->tax_percentage_code = $computedTax['tax_percentage_code'];
            $itemTax->tax_calculation_type = $computedTax['tax_calculation_type'];
            $itemTax->base_amount = $computedTax['base_amount'];
            $itemTax->tax_amount = $computedTax['tax_amount'];
            $itemTax->save();
        }

        $item->discount_amount = $discountAmount;
        $item->subtotal = $subtotal;
        $item->tax_amount = $computation['tax_amount'];
        $item->total = $computation['total'];
        $item->save();
    }

    /**
     * @param  list<array<string, mixed>>  $reasons
     */
    private function recalculateFromReasons(DebitNote $debitNote, array $reasons): void
    {
        $normalizedReasons = collect($reasons)
            ->map(static fn (array $reason): array => [
                'reason' => mb_trim((string) ($reason['reason'] ?? '')),
                'value' => number_format((float) ($reason['value'] ?? 0), 4, '.', ''),
            ])
            ->values();

        $subtotal = number_format(
            $normalizedReasons->sum(static fn (array $reason): float => (float) ($reason['value'] ?? 0)),
            4,
            '.',
            '',
        );

        $taxRate = (float) ($debitNote->tax_rate ?? 0);
        $taxAmount = number_format(((float) $subtotal * $taxRate) / 100, 4, '.', '');
        $total = number_format((float) $subtotal + (float) $taxAmount, 4, '.', '');

        $debitNote->subtotal = $subtotal;
        $debitNote->tax_amount = $taxAmount;
        $debitNote->total = $total;
        $debitNote->reasons = $normalizedReasons->all();
        $debitNote->saveQuietly();
    }

    private function recalculateFromItems(DebitNote $debitNote): void
    {
        $subtotal = '0.0000';
        $taxAmount = '0.0000';

        foreach ($debitNote->items as $item) {
            $this->recalculateItem($item);

            $subtotal = bcadd($subtotal, $item->subtotal, 4);
            $taxAmount = bcadd($taxAmount, $item->tax_amount, 4);
        }

        $debitNote->subtotal = $subtotal;
        $debitNote->tax_amount = $taxAmount;
        $debitNote->total = bcadd($subtotal, $taxAmount, 4);
        $debitNote->reasons = $debitNote->items
            ->map(fn (DebitNoteItem $item): array => [
                'reason' => mb_trim((string) ($item->description ?: $item->product_name ?: __('Additional charge'))),
                'value' => $item->subtotal,
            ])
            ->values()
            ->all();
        $debitNote->saveQuietly();
    }
}
