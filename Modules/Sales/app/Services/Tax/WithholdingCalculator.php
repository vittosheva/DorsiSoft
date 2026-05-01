<?php

declare(strict_types=1);

namespace Modules\Sales\Services\Tax;

use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Models\PurchaseSettlementItem;
use Modules\Sales\Models\TaxWithholdingRule;
use Modules\Sales\Services\WithholdingCalculationService;

final class WithholdingCalculator
{
    public function __construct(private readonly WithholdingCalculationService $calc) {}

    /**
     * @param  string|null  $concept  // e.g. 'servicios'|'bienes'|'profesionales'
     * @return array<string, mixed>
     */
    public function calculate(PurchaseSettlement $settlement, ?string $concept = 'servicios'): array
    {
        $settlement->loadMissing('items');

        $subtotal = (float) $settlement->subtotal;
        $ivaTotal = (float) $settlement->tax_amount; // assume tax_amount represents IVA per requirement

        // IVA retenido: 100% of IVA total
        $ivaWithheld = (float) $this->calc->calculateWithheld($ivaTotal, 100);

        // Find renta rule for company or global
        $rule = TaxWithholdingRule::query()
            ->where(function ($q) use ($settlement) {
                $q->where('company_id', $settlement->company_id)
                    ->orWhereNull('company_id');
            })
            ->where('type', 'renta')
            ->where('concept', $concept)
            ->orderByRaw('company_id IS NULL')
            ->first();

        $rentaPercentage = (float) ($rule?->percentage ?? 0.0);
        $rentaWithheld = (float) $this->calc->calculateWithheld($subtotal, $rentaPercentage);

        $items = [];
        foreach ($settlement->items as $item) {
            /** @var PurchaseSettlementItem $item */
            $itemIva = (float) $item->tax_amount;
            $itemIvaWithheld = (float) $this->calc->calculateWithheld($itemIva, 100);

            // proportion renta by item subtotal
            $itemRentaWithheld = 0.0;
            if ($subtotal > 0 && $rentaWithheld > 0) {
                $itemRentaWithheld = round($rentaWithheld * ((float) $item->subtotal / $subtotal), 2);
            }

            $items[] = [
                'id' => $item->getKey(),
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'tax_amount' => $itemIva,
                'iva_withheld' => $itemIvaWithheld,
                'renta_withheld' => $itemRentaWithheld,
            ];
        }

        $totalWithheld = $ivaWithheld + $rentaWithheld;
        $netPayable = (float) $settlement->total - $totalWithheld;

        return [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'iva_total' => number_format($ivaTotal, 2, '.', ''),
            'iva_withheld' => number_format($ivaWithheld, 2, '.', ''),
            'renta_percentage' => number_format($rentaPercentage, 2, '.', ''),
            'renta_withheld' => number_format($rentaWithheld, 2, '.', ''),
            'items' => $items,
            'total_withheld' => number_format($totalWithheld, 2, '.', ''),
            'net_payable' => number_format($netPayable, 2, '.', ''),
        ];
    }
}
