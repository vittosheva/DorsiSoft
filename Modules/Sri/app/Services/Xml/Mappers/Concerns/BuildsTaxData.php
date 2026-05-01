<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Mappers\Concerns;

use Illuminate\Support\Collection;
use Modules\Sales\Models\CreditNoteItemTax;
use Modules\Sales\Models\DebitNoteItemTax;
use Modules\Sales\Models\InvoiceItemTax;
use Modules\Sri\DTOs\SriTaxLineData;
use Modules\Sri\Exceptions\XmlGenerationException;

trait BuildsTaxData
{
    /**
     * @param  iterable<InvoiceItemTax|CreditNoteItemTax|DebitNoteItemTax>  $taxes
     *
     * @throws XmlGenerationException
     */
    private function ensureUniqueTaxCodes(iterable $taxes, string $documentTypeCode = '', ?string $itemDescription = null): void
    {
        $seenCodes = [];
        $duplicateCodes = [];

        foreach ($taxes as $tax) {
            $codigo = $this->resolveSriTaxCode($tax, $documentTypeCode);

            if (isset($seenCodes[$codigo])) {
                $duplicateCodes[$codigo] = $codigo;

                continue;
            }

            $seenCodes[$codigo] = true;
        }

        if ($duplicateCodes === []) {
            return;
        }

        $itemPrefix = filled($itemDescription)
            ? "El ítem '{$itemDescription}'"
            : 'El ítem';

        throw new XmlGenerationException(
            $itemPrefix
                .' contiene impuestos repetidos con el mismo código SRI: '
                .implode(', ', array_values($duplicateCodes))
                .'.'
        );
    }

    /**
     * Maps an InvoiceItemTax or CreditNoteItemTax model to a SriTaxLineData DTO.
     */
    private function mapItemTax(InvoiceItemTax|CreditNoteItemTax|DebitNoteItemTax $tax, string $documentTypeCode = ''): SriTaxLineData
    {
        $rate = (float) $tax->tax_rate;

        return new SriTaxLineData(
            codigo: $this->resolveSriTaxCode($tax, $documentTypeCode),
            codigoPorcentaje: $this->resolveSriTaxPercentageCode($tax, $rate),
            tarifa: number_format($rate, 2, '.', ''),
            baseImponible: number_format((float) $tax->base_amount, 2, '.', ''),
            valor: number_format((float) $tax->tax_amount, 2, '.', ''),
        );
    }

    /**
     * Aggregates item taxes into document-level tax totals, grouped by (codigo, codigoPorcentaje).
     *
     * @param  iterable<InvoiceItemTax|CreditNoteItemTax|DebitNoteItemTax>  $allItemTaxes
     * @return list<SriTaxLineData>
     */
    private function aggregateTaxLines(iterable $allItemTaxes, string $documentTypeCode = ''): array
    {
        /** @var array<string, array{codigo: string, codigoPorcentaje: string, tarifa: string, base: float, valor: float}> $groups */
        $groups = [];

        foreach ($this->sortTaxes($allItemTaxes, $documentTypeCode) as $tax) {
            $rate = (float) $tax->tax_rate;
            $codigo = $this->resolveSriTaxCode($tax, $documentTypeCode);
            $codigoPorcentaje = $this->resolveSriTaxPercentageCode($tax, $rate);
            $key = "{$codigo}_{$codigoPorcentaje}";

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'codigo' => $codigo,
                    'codigoPorcentaje' => $codigoPorcentaje,
                    'tarifa' => number_format($rate, 2, '.', ''),
                    'base' => 0.0,
                    'valor' => 0.0,
                ];
            }

            $groups[$key]['base'] += (float) $tax->base_amount;
            $groups[$key]['valor'] += (float) $tax->tax_amount;
        }

        $lines = array_values(array_map(
            fn (array $g) => new SriTaxLineData(
                codigo: $g['codigo'],
                codigoPorcentaje: $g['codigoPorcentaje'],
                tarifa: $g['tarifa'],
                baseImponible: number_format($g['base'], 2, '.', ''),
                valor: number_format($g['valor'], 2, '.', ''),
            ),
            $groups,
        ));

        usort($lines, fn (SriTaxLineData $left, SriTaxLineData $right): int => $this->taxCodeSortOrder($left->codigo) <=> $this->taxCodeSortOrder($right->codigo));

        return $lines;
    }

    /**
     * @param  iterable<InvoiceItemTax|CreditNoteItemTax|DebitNoteItemTax>  $taxes
     * @return Collection<int, InvoiceItemTax|CreditNoteItemTax|DebitNoteItemTax>
     */
    private function sortTaxes(iterable $taxes, string $documentTypeCode = ''): Collection
    {
        return collect($taxes)
            ->sortBy(fn (InvoiceItemTax|CreditNoteItemTax|DebitNoteItemTax $tax): int => $this->taxCodeSortOrder($this->resolveSriTaxCode($tax, $documentTypeCode)))
            ->values();
    }

    private function resolveSriTaxCode(InvoiceItemTax|CreditNoteItemTax|DebitNoteItemTax $tax, string $documentTypeCode = ''): string
    {
        $derivedCode = $this->taxTypeToCodigo((string) $tax->tax_type, $documentTypeCode);

        return filled($tax->tax_code)
            ? (string) $tax->tax_code
            : $derivedCode;
    }

    private function resolveSriTaxPercentageCode(InvoiceItemTax|CreditNoteItemTax|DebitNoteItemTax $tax, float $rate): string
    {
        return filled($tax->tax_percentage_code)
            ? (string) $tax->tax_percentage_code
            : $this->ivaRateToCodigoPorcentaje((string) $tax->tax_type, $rate);
    }

    private function taxCodeSortOrder(string $codigo): int
    {
        return match ($codigo) {
            '3' => 0,
            '2' => 1,
            default => 2,
        };
    }

    /**
     * Maps Finance TaxTypeEnum value to SRI tax category code.
     * '2' = IVA, '3' = ICE, '5' = IRBPNR, '6' = ISD
     *
     * @throws XmlGenerationException When the tax type is not valid for the given document type
     */
    private function taxTypeToCodigo(string $taxType, string $documentTypeCode = ''): string
    {
        /** @var array<string, list<string>> $unsupported */
        $unsupported = [
            '01' => ['ISD'],
            '04' => ['ISD'],
            '05' => ['ISD'],
        ];

        if (
            $documentTypeCode !== ''
            && isset($unsupported[$documentTypeCode])
            && in_array(mb_strtoupper($taxType), $unsupported[$documentTypeCode], true)
        ) {
            throw new XmlGenerationException(
                "El tipo de impuesto '{$taxType}' no es válido para el tipo de documento '{$documentTypeCode}'. "
                    .'El XSD de facturas, notas de crédito y notas de débito solo admite IVA (2), ICE (3) e IRBPNR (5).'
            );
        }

        return match (mb_strtoupper($taxType)) {
            'IVA' => '2',
            'ICE' => '3',
            'IRBPNR' => '5',
            'ISD' => '6',
            default => '2',
        };
    }

    /**
     * Maps IVA rate (%) to SRI codigoPorcentaje.
     * For non-IVA taxes, returns the rate formatted as integer string.
     */
    private function ivaRateToCodigoPorcentaje(string $taxType, float $rate): string
    {
        if (mb_strtoupper($taxType) !== 'IVA') {
            return (string) (int) $rate;
        }

        return match (true) {
            $rate === 0.0 => '0',
            $rate === 5.0 => '6',
            $rate === 8.0 => '8',
            $rate === 12.0 => '2',
            $rate === 13.0 => '3',
            $rate === 14.0 => '3',
            $rate === 15.0 => '4',
            default => '2',
        };
    }
}
