<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use BackedEnum;
use Illuminate\Support\Collection;
use Modules\System\Enums\TaxCalculationTypeEnum;
use Modules\System\Models\TaxRuleLine;

final class ItemTaxComputationService
{
    /**
     * @param  iterable<mixed>  $taxes
     * @return array{taxes: list<array{source: mixed, tax_type: string, tax_code: string, tax_percentage_code: string, tax_rate: string, tax_calculation_type: string, base_amount: string, tax_amount: string}>, tax_amount: string, total: string, ice_amount: string, iva_amount: string}
     */
    public function compute(string $quantity, string $subtotal, iterable $taxes): array
    {
        /** @var Collection<int, array{source: mixed, tax_type: string, tax_code: string, tax_percentage_code: string, tax_rate: string, tax_calculation_type: string}> $preparedTaxes */
        $preparedTaxes = collect($taxes)
            ->map(fn (mixed $tax): array => [
                'source' => $tax,
                'tax_type' => $this->resolveTaxType($tax),
                'tax_code' => $this->resolveTaxCode($tax),
                'tax_percentage_code' => $this->resolveTaxPercentageCode($tax),
                'tax_rate' => $this->resolveDecimal($tax, ['tax_rate', 'rate']),
                'tax_calculation_type' => $this->resolveCalculationType($tax),
            ])
            ->sortBy(fn (array $tax): int => $this->sortOrder($tax['tax_type']))
            ->values();

        $iceAmount = '0.0000';
        $ivaAmount = '0.0000';
        $taxAmount = '0.0000';

        $computedTaxes = $preparedTaxes->map(function (array $tax) use ($quantity, $subtotal, &$iceAmount, &$ivaAmount, &$taxAmount): array {
            $baseAmount = $tax['tax_type'] === 'IVA'
                ? bcadd($subtotal, $iceAmount, 4)
                : $subtotal;

            $computedTaxAmount = $tax['tax_calculation_type'] === TaxCalculationTypeEnum::Fixed->value
                ? bcmul($quantity, $tax['tax_rate'], 4)
                : bcmul($baseAmount, bcdiv($tax['tax_rate'], '100', 8), 4);

            if ($tax['tax_type'] === 'ICE') {
                $iceAmount = bcadd($iceAmount, $computedTaxAmount, 4);
            }

            if ($tax['tax_type'] === 'IVA') {
                $ivaAmount = bcadd($ivaAmount, $computedTaxAmount, 4);
            }

            $taxAmount = bcadd($taxAmount, $computedTaxAmount, 4);

            return array_merge($tax, [
                'base_amount' => $baseAmount,
                'tax_amount' => $computedTaxAmount,
            ]);
        })->all();

        return [
            'taxes' => $computedTaxes,
            'tax_amount' => $taxAmount,
            'total' => bcadd($subtotal, $taxAmount, 4),
            'ice_amount' => $iceAmount,
            'iva_amount' => $ivaAmount,
        ];
    }

    /**
     * Compute IR progressive tax using bracket lines (tramos).
     *
     * Each bracket stores: from_amount, to_amount, excess_from, fixed_amount (impuesto fracción básica), rate (% marginal).
     * Formula: fixed_amount + (base - excess_from) * rate / 100
     *
     * @param  Collection<int, TaxRuleLine>  $lines  Ordered by sort_order ASC
     */
    public function computeProgressive(string $base, Collection $lines): string
    {
        $baseNum = (float) $base;
        $result = '0.0000';

        foreach ($lines as $line) {
            $from = (float) ($line->from_amount ?? 0);
            $to = $line->to_amount !== null ? (float) $line->to_amount : PHP_FLOAT_MAX;

            if ($baseNum < $from || $baseNum <= 0) {
                continue;
            }

            if ($baseNum >= $from && ($line->to_amount === null || $baseNum <= $to)) {
                $excessFrom = (float) ($line->excess_from ?? $from);
                $fixedAmount = (string) ($line->fixed_amount ?? 0);
                $marginal = bcmul(
                    bcsub($base, number_format($excessFrom, 4, '.', ''), 4),
                    bcdiv(number_format((float) $line->rate, 4, '.', ''), '100', 8),
                    4
                );

                $result = bcadd($fixedAmount, $marginal, 4);
                break;
            }
        }

        return $result;
    }

    /**
     * Compute ICE mixed (specific + percentage) for a single product line.
     *
     * - Fixed: qty * fixed_amount per unit
     * - Percentage: pvp * rate / 100
     * - Mixed: both combined
     */
    public function computeIceMixed(string $quantity, string $pvp, TaxRuleLine $line): string
    {
        $specificAmount = '0.0000';
        $percentageAmount = '0.0000';

        if (bccomp((string) $line->fixed_amount, '0', 4) > 0) {
            $specificAmount = bcmul($quantity, (string) $line->fixed_amount, 4);
        }

        if (bccomp((string) $line->rate, '0', 4) > 0) {
            $percentageAmount = bcmul($pvp, bcdiv((string) $line->rate, '100', 8), 4);
        }

        return bcadd($specificAmount, $percentageAmount, 4);
    }

    private function sortOrder(string $taxType): int
    {
        return match ($taxType) {
            'ICE' => 0,
            'IVA' => 1,
            default => 2,
        };
    }

    private function resolveTaxType(mixed $tax): string
    {
        $value = $this->extractValue($tax, ['tax_type', 'type']) ?? 'IVA';

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return mb_strtoupper(mb_trim((string) $value));
    }

    private function resolveTaxCode(mixed $tax): string
    {
        $resolvedType = $this->resolveTaxType($tax);
        $value = $this->extractValue($tax, ['tax_code', 'sri_code']);

        if (filled($value)) {
            return (string) $value;
        }

        return match ($resolvedType) {
            'ICE' => '3',
            'ISD' => '6',
            default => '2',
        };
    }

    private function resolveTaxPercentageCode(mixed $tax): string
    {
        $value = $this->extractValue($tax, ['tax_percentage_code', 'sri_percentage_code']);

        if (filled($value)) {
            return (string) $value;
        }

        $taxType = $this->resolveTaxType($tax);
        $rate = (float) $this->resolveDecimal($tax, ['tax_rate', 'rate']);

        if ($taxType !== 'IVA') {
            return (string) (int) round($rate, 0);
        }

        return match (true) {
            $rate === 0.0 => '0',
            $rate === 5.0 => '6',
            $rate === 8.0 => '8',
            $rate === 12.0 => '2',
            $rate === 13.0, $rate === 14.0 => '3',
            $rate === 15.0 => '4',
            default => '2',
        };
    }

    private function resolveCalculationType(mixed $tax): string
    {
        $value = $this->extractValue($tax, ['tax_calculation_type', 'calculation_type']);

        if ($value instanceof TaxCalculationTypeEnum) {
            return $value->value;
        }

        if (is_string($value) && TaxCalculationTypeEnum::tryFrom($value) !== null) {
            return $value;
        }

        return TaxCalculationTypeEnum::Percentage->value;
    }

    /**
     * @param  list<string>  $keys
     */
    private function resolveDecimal(mixed $tax, array $keys): string
    {
        $value = $this->extractValue($tax, $keys);

        return number_format((float) $value, 4, '.', '');
    }

    /**
     * @param  list<string>  $keys
     */
    private function extractValue(mixed $subject, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (is_array($subject) && array_key_exists($key, $subject)) {
                return $subject[$key];
            }

            if (is_object($subject) && isset($subject->{$key})) {
                return $subject->{$key};
            }
        }

        return null;
    }
}
