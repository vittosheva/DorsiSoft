<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Contract for all document totals calculators.
 *
 * Every transactional document (Quotation, SalesOrder, Invoice, PurchaseOrder, etc.)
 * must have a dedicated calculator service that implements this interface.
 * This ensures consistent recalculation behavior across all document types.
 */
interface DocumentTotalsCalculator
{
    /**
     * Recalculate all line items and header totals for the given document, then persist.
     */
    public function recalculate(Model $document): void;

    /**
     * Recalculate a single document line item and its taxes, then persist.
     */
    public function recalculateItem(Model $item): void;
}
