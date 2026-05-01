<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Pdf;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\System\Enums\TaxCalculationTypeEnum;

final class CommercialDocumentPdfDataBuilder
{
    private const FIXED_VALUE_LABEL = 'Fixed value';

    /**
     * @return array{taxBreakdown: list<array{label: string, base_amount: string, tax_amount: string}>, totalDiscountAmount: string}
     */
    public static function build(Model $document): array
    {
        /** @var Collection<int, mixed> $items */
        $items = collect($document->getRelationValue('items') ?? []);

        $itemDiscountSum = $items->sum('discount_amount');

        return [
            'taxBreakdown' => self::buildTaxBreakdown($items),
            'totalDiscountAmount' => self::normalizeAmount($itemDiscountSum),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return list<array{label: string, base_amount: string, tax_amount: string}>
     */
    private static function buildTaxBreakdown(Collection $items): array
    {
        return $items
            ->flatMap(static function (mixed $item): array {
                return array_values($item?->getRelationValue('taxes')?->all() ?? []);
            })
            ->groupBy(static function (mixed $itemTax): string {
                $type = (string) ($itemTax->tax_type ?? 'Tax');
                $name = (string) ($itemTax->tax_name ?? $type);
                $rate = self::normalizeRate($itemTax->tax_rate ?? null);
                $calculationType = $itemTax->tax_calculation_type;

                $suffix = $calculationType === TaxCalculationTypeEnum::Fixed
                    ? self::FIXED_VALUE_LABEL
                    : $rate.'%';

                return implode('|', [$type, $name, $suffix]);
            })
            ->map(static function (Collection $group, string $key): array {
                [$type, $name, $suffix] = explode('|', $key);

                $label = $suffix === self::FIXED_VALUE_LABEL
                    ? sprintf('%s (%s)', $name, $suffix)
                    : sprintf('%s (%s)', $name, $suffix);

                $baseAmount = $group->reduce(
                    static fn (string $carry, mixed $itemTax): string => bcadd($carry, self::normalizeAmount($itemTax->base_amount ?? null), 4),
                    '0.0000',
                );

                $taxAmount = $group->reduce(
                    static fn (string $carry, mixed $itemTax): string => bcadd($carry, self::normalizeAmount($itemTax->tax_amount ?? null), 4),
                    '0.0000',
                );

                return [
                    'label' => $label !== '' ? $label : $type,
                    'base_amount' => $baseAmount,
                    'tax_amount' => $taxAmount,
                ];
            })
            ->values()
            ->all();
    }

    private static function normalizeAmount(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.0000';
        }

        return number_format((float) $value, 4, '.', '');
    }

    private static function normalizeRate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return mb_rtrim(mb_rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
