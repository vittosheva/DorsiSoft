<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Contracts\DocumentTotalsCalculator;
use Modules\Sales\Enums\DiscountTypeEnum;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\QuotationItem;
use Modules\Sales\Services\Concerns\ComputesItemDiscount;

final class QuotationTotalsCalculator implements DocumentTotalsCalculator
{
    use ComputesItemDiscount;

    public function __construct(private readonly ItemTaxComputationService $itemTaxComputationService) {}

    /**
     * Recalculate all totals for a quotation and persist changes.
     *
     * @param  Quotation  $document
     */
    public function recalculate(Model $document): void
    {
        /** @var Quotation $quotation */
        $quotation = $document;
        $quotation->loadMissing(['items.taxes']);

        $subtotal = '0.0000';
        $taxBase = '0.0000';
        $taxAmount = '0.0000';

        foreach ($quotation->items as $item) {
            $this->recalculateItem($item);

            $subtotal = bcadd($subtotal, $item->subtotal, 4);
            $taxAmount = bcadd($taxAmount, $item->tax_amount, 4);

            if ($item->taxes->isNotEmpty()) {
                $taxBase = bcadd($taxBase, $item->subtotal, 4);
            }
        }

        $discountAmount = $this->computeGlobalDiscount($quotation, $subtotal);
        $total = bcsub(bcadd($subtotal, $taxAmount, 4), $discountAmount, 4);

        $quotation->subtotal = $subtotal;
        $quotation->tax_base = $taxBase;
        $quotation->discount_amount = $discountAmount;
        $quotation->tax_amount = $taxAmount;
        $quotation->total = $total;
        $quotation->saveQuietly();
    }

    /**
     * Recalculate a single QuotationItem and its taxes, then persist.
     *
     * @param  QuotationItem  $item
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

    private function computeGlobalDiscount(Quotation $quotation, string $subtotal): string
    {
        if ($quotation->discount_value === null || $quotation->discount_type === null) {
            return '0.0000';
        }

        if ($quotation->discount_type === DiscountTypeEnum::Percentage) {
            return bcmul($subtotal, bcdiv((string) $quotation->discount_value, '100', 8), 4);
        }

        return min((string) $quotation->discount_value, $subtotal);
    }
}
