<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Contracts\DocumentTotalsCalculator;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\CreditNoteItem;
use Modules\Sales\Services\Concerns\ComputesItemDiscount;

final class CreditNoteTotalsCalculator implements DocumentTotalsCalculator
{
    use ComputesItemDiscount;

    public function __construct(private readonly ItemTaxComputationService $itemTaxComputationService) {}

    /**
     * Recalculate all totals for a credit note and persist changes.
     *
     * @param  CreditNote  $document
     */
    public function recalculate(Model $document): void
    {
        /** @var CreditNote $creditNote */
        $creditNote = $document;
        $creditNote->loadMissing(['items.taxes']);

        $subtotal = '0.0000';
        $taxAmount = '0.0000';

        foreach ($creditNote->items as $item) {
            $this->recalculateItem($item);

            $subtotal = bcadd($subtotal, $item->subtotal, 4);
            $taxAmount = bcadd($taxAmount, $item->tax_amount, 4);
        }

        $creditNote->subtotal = $subtotal;
        $creditNote->tax_amount = $taxAmount;
        $creditNote->total = bcadd($subtotal, $taxAmount, 4);
        $creditNote->saveQuietly();
    }

    /**
     * Recalculate a single CreditNoteItem and its taxes, then persist.
     *
     * @param  CreditNoteItem  $item
     */
    public function recalculateItem(Model $item): void
    {
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
}
