<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\System\Enums\TaxGroupEnum;
use Modules\System\Enums\WithholdingAppliesToEnum;
use Modules\System\Models\TaxWithholdingRate;

final class WithholdingSuggestionService
{
    /**
     * Suggest withholding line items based on source document amounts and context.
     *
     * @return array<int, array{
     *     tax_type: string,
     *     withholding_rate_id: int,
     *     tax_code: string,
     *     tax_rate: string,
     *     base_amount: string,
     *     withheld_amount: string,
     * }>
     */
    public function suggestItems(
        float $subtotal,
        float $ivaAmount,
        WithholdingAppliesToEnum $appliesTo = WithholdingAppliesToEnum::Ambos,
    ): array {
        $suggestions = [];

        $rates = TaxWithholdingRate::query()
            ->with('taxDefinition:id,tax_group,sri_code')
            ->active()
            ->get();

        foreach ($rates as $rate) {
            $taxGroup = $rate->taxDefinition?->tax_group;

            if (! $taxGroup instanceof TaxGroupEnum) {
                continue;
            }

            if (! $this->rateMatchesAppliesTo($rate, $appliesTo)) {
                continue;
            }

            [$taxType, $base] = match ($taxGroup) {
                TaxGroupEnum::Renta => ['IR', $subtotal],
                TaxGroupEnum::Iva => ['IVA', $ivaAmount],
                default => [null, null],
            };

            if ($taxType === null || $base <= 0) {
                continue;
            }

            $withheld = number_format($base * (float) $rate->percentage / 100, 2, '.', '');

            $suggestions[] = [
                'tax_type' => $taxType,
                'withholding_rate_id' => $rate->getKey(),
                'tax_code' => $rate->sri_code,
                'tax_rate' => $rate->percentage,
                'base_amount' => number_format($base, 2, '.', ''),
                'withheld_amount' => $withheld,
            ];
        }

        return $suggestions;
    }

    private function rateMatchesAppliesTo(TaxWithholdingRate $rate, WithholdingAppliesToEnum $appliesTo): bool
    {
        $rateAppliesTo = $rate->applies_to;

        if (! $rateAppliesTo instanceof WithholdingAppliesToEnum) {
            return true;
        }

        if ($rateAppliesTo === WithholdingAppliesToEnum::Ambos) {
            return true;
        }

        if ($appliesTo === WithholdingAppliesToEnum::Ambos) {
            return true;
        }

        return $rateAppliesTo === $appliesTo;
    }
}
