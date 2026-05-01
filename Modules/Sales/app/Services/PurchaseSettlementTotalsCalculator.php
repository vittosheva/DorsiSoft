<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Contracts\DocumentTotalsCalculator;
use Modules\Sales\Models\PurchaseSettlement;

final class PurchaseSettlementTotalsCalculator implements DocumentTotalsCalculator
{
    public function recalculate(Model $document): void
    {
        /** @var PurchaseSettlement $settlement */
        $settlement = $document;
        $settlement->loadMissing('items');

        $subtotal = '0.0000';
        $taxBase = '0.0000';
        $taxAmount = '0.0000';

        foreach ($settlement->items as $item) {
            $subtotal = bcadd($subtotal, (string) $item->subtotal, 4);
            $taxAmount = bcadd($taxAmount, (string) $item->tax_amount, 4);

            if (bccomp((string) $item->tax_amount, '0', 4) > 0) {
                $taxBase = bcadd($taxBase, (string) $item->subtotal, 4);
            }
        }

        $settlement->subtotal = $subtotal;
        $settlement->tax_base = $taxBase;
        $settlement->tax_amount = $taxAmount;
        $settlement->total = bcadd($subtotal, $taxAmount, 4);
        $settlement->saveQuietly();
    }

    public function recalculateItem(Model $item): void
    {
        // Purchase settlement items store a flat tax_amount; no subtable to recompute.
    }
}
