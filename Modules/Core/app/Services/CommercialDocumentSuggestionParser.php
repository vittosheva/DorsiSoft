<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Carbon\CarbonImmutable;
use Throwable;

final class CommercialDocumentSuggestionParser
{
    /**
     * @return array{headers: array<string, string>, items: array<int, array<string, mixed>>, confidence_score: float}
     */
    public function parse(string $rawText): array
    {
        $lines = $this->normalizeLines($rawText);
        $headers = $this->extractHeaders($lines);
        $items = $this->extractItems($lines);

        return [
            'headers' => $headers,
            'items' => $items,
            'confidence_score' => $this->estimateConfidence($headers, $items),
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizeLines(string $rawText): array
    {
        $lines = preg_split('/\R/u', $rawText) ?: [];

        return array_values(array_filter(array_map(function (string $line): string {
            $line = preg_replace('/\s+/u', ' ', mb_trim($line)) ?? '';

            return mb_trim($line);
        }, $lines), fn (string $line): bool => $line !== ''));
    }

    /**
     * @param  list<string>  $lines
     * @return array<string, string>
     */
    private function extractHeaders(array $lines): array
    {
        $headers = [];

        foreach ($lines as $line) {
            if (! isset($headers['customer_name']) && preg_match('/(?:cliente|customer|raz[oó]n social|señor(?:es)?)\s*[:\-]?\s*(.+)$/iu', $line, $matches) === 1) {
                $headers['customer_name'] = mb_trim($matches[1]);
            }

            if (! isset($headers['identification_number']) && preg_match('/(?:ruc|dni|ci|c[ée]dula|identificaci[oó]n)\s*[:#\-]?\s*([0-9]{8,13})/iu', $line, $matches) === 1) {
                $headers['identification_number'] = $matches[1];
            }

            if (! isset($headers['issue_date']) && preg_match('/\b(\d{2}[\/\-]\d{2}[\/\-]\d{4}|\d{4}[\/\-]\d{2}[\/\-]\d{2})\b/u', $line, $matches) === 1) {
                $normalizedDate = $this->normalizeDate($matches[1]);

                if ($normalizedDate !== null) {
                    $headers['issue_date'] = $normalizedDate;
                }
            }

            if (! isset($headers['reference']) && preg_match('/(?:referencia|ref\.?|documento|n[úu]mero)\s*[:\-]?\s*(.+)$/iu', $line, $matches) === 1) {
                $headers['reference'] = mb_trim($matches[1]);
            }

            if (! isset($headers['reference']) && preg_match('/^No\.\s*(.+)$/iu', $line, $matches) === 1) {
                $headers['reference'] = mb_trim($matches[1]);
            }

            if (! isset($headers['notes']) && preg_match('/(?:observaciones|nota(?:s)?|comentarios?)\s*[:\-]?\s*(.+)$/iu', $line, $matches) === 1) {
                $headers['notes'] = mb_trim($matches[1]);
            }
        }

        if (! isset($headers['customer_name'])) {
            $headers['customer_name'] = $this->extractCustomerNameAroundDate($lines);
        }

        if (isset($headers['customer_name']) && ! $this->looksLikeCustomerName($headers['customer_name'])) {
            $resolvedCustomerName = $this->extractCustomerNameAroundDate($lines);

            if ($resolvedCustomerName !== null) {
                $headers['customer_name'] = $resolvedCustomerName;
            }
        }

        return array_filter($headers, fn (string $value): bool => $value !== '');
    }

    /**
     * @param  list<string>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function extractItems(array $lines): array
    {
        $items = $this->extractStructuredItems($lines);

        if ($items !== []) {
            return $items;
        }

        $items = [];

        foreach ($lines as $line) {
            $item = $this->parseItemLine($line);

            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param  list<string>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function extractStructuredItems(array $lines): array
    {
        $items = [];
        $itemLines = $this->extractReceiptItemLines($lines);

        if ($itemLines === []) {
            return [];
        }

        $unitPrices = $this->extractPriceList($lines, ['Precio Unitario Descuento', 'Unitario Descuento'], ['Precio Total', 'SUBTOTAL', 'ICE', 'IVA', 'PROPINA', 'VALOR TOTAL']);
        $lineTotals = $this->extractPriceList($lines, ['Precio Total'], ['SUBTOTAL', 'ICE', 'IVA', 'PROPINA', 'VALOR TOTAL']);

        foreach ($itemLines as $index => $itemLine) {
            $quantity = (float) $itemLine['quantity'];
            $lineTotal = $lineTotals[$index] ?? null;
            $unitPrice = $unitPrices[$index] ?? null;

            if ($unitPrice === null && $lineTotal !== null && $quantity > 0) {
                $unitPrice = round($lineTotal / $quantity, 4);
            }

            if ($lineTotal === null && $unitPrice !== null) {
                $lineTotal = round($quantity * $unitPrice, 4);
            }

            $items[] = [
                'product_id' => null,
                'product_code' => $itemLine['product_code'],
                'product_name' => $itemLine['description'],
                'product_unit' => null,
                'description' => $itemLine['description'],
                'detail_1' => null,
                'detail_2' => null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice ?? 0.0,
                'line_total' => $lineTotal ?? 0.0,
                'discount_type' => null,
                'discount_value' => null,
                'taxes' => [],
            ];
        }

        return array_values(array_filter($items, fn (array $item): bool => $item['description'] !== ''));
    }

    /**
     * @param  list<string>  $lines
     * @return array<int, array{product_code: string|null, quantity: float, description: string}>
     */
    private function extractReceiptItemLines(array $lines): array
    {
        $startIndex = $this->findLineIndexContaining($lines, ['Cant Descrip']);

        if ($startIndex === null) {
            return [];
        }

        $items = [];

        for ($index = $startIndex + 1, $count = count($lines); $index < $count; $index++) {
            $line = $lines[$index];

            if ($this->lineContainsAny($line, ['Informaci', 'FACTURA', 'Forma de Pago', 'Precio Unitario', 'SUBTOTAL'])) {
                break;
            }

            if (preg_match('/^(?:(\d{2,6})\s+)?(\d+(?:[\.,]\d{1,4})?)\s+(.+)$/u', $line, $matches) !== 1) {
                continue;
            }

            $description = mb_trim($matches[3]);

            if (! $this->looksLikeItemDescription($description)) {
                continue;
            }

            $items[] = [
                'product_code' => isset($matches[1]) ? mb_trim($matches[1]) : null,
                'quantity' => $this->normalizeNumericValue($matches[2]),
                'description' => $description,
            ];
        }

        return $items;
    }

    /**
     * @param  list<string>  $lines
     * @param  list<string>  $startsAfter
     * @param  list<string>  $stopsBefore
     * @return list<float>
     */
    private function extractPriceList(array $lines, array $startsAfter, array $stopsBefore): array
    {
        $startIndex = $this->findLineIndexContaining($lines, $startsAfter);

        if ($startIndex === null) {
            return [];
        }

        $values = [];

        for ($index = $startIndex + 1, $count = count($lines); $index < $count; $index++) {
            $line = $lines[$index];

            if ($this->lineContainsAny($line, $stopsBefore)) {
                break;
            }

            if (preg_match('/^([\d\.,]+)(?:\s+[\d\.,]+)?$/u', $line, $matches) !== 1) {
                continue;
            }

            $values[] = $this->normalizeNumericValue($matches[1]);
        }

        return $values;
    }

    /**
     * @param  list<string>  $lines
     * @param  list<string>  $needles
     */
    private function findLineIndexContaining(array $lines, array $needles): ?int
    {
        foreach ($lines as $index => $line) {
            if ($this->lineContainsAny($line, $needles)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $needles
     */
    private function lineContainsAny(string $line, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (mb_stripos($line, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $lines
     */
    private function extractCustomerNameAroundDate(array $lines): ?string
    {
        foreach ($lines as $index => $line) {
            if ($this->normalizeDateFromLine($line) === null) {
                continue;
            }

            for ($lookBehind = $index - 1; $lookBehind >= max(0, $index - 4); $lookBehind--) {
                $candidate = $lines[$lookBehind];

                if ($this->looksLikeCustomerName($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function normalizeDateFromLine(string $line): ?string
    {
        if (preg_match('/\b(\d{2}[\/\-]\d{2}[\/\-]\d{4}|\d{4}[\/\-]\d{2}[\/\-]\d{2})\b/u', $line, $matches) !== 1) {
            return null;
        }

        return $this->normalizeDate($matches[1]);
    }

    private function looksLikeCustomerName(string $value): bool
    {
        $value = mb_trim($value);

        if ($value === '' || mb_strlen($value) < 3) {
            return false;
        }

        if ($this->lineContainsAny($value, ['cliente', 'customer', 'razón social', 'nombres', 'fecha emisión', 'autorización', 'r.u.c', 'ruc'])) {
            return false;
        }

        return preg_match('/\p{L}{2,}/u', $value) === 1;
    }

    private function looksLikeItemDescription(string $value): bool
    {
        $value = mb_trim($value);

        if ($value === '' || preg_match('/\p{L}/u', $value) !== 1) {
            return false;
        }

        return ! $this->lineContainsAny($value, ['subtotal', 'precio', 'información', 'forma de pago', 'valor total']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseItemLine(string $line): ?array
    {
        if (preg_match('/^\s*(\d+(?:[\.,]\d{1,4})?)\s+(.+?)\s+([\$€]?[\d\.,]+)\s+([\$€]?[\d\.,]+)\s*$/u', $line, $matches) !== 1) {
            return null;
        }

        $quantity = $this->normalizeNumericValue($matches[1]);
        $description = mb_trim($matches[2]);
        $unitPrice = $this->normalizeNumericValue($matches[3]);
        $lineTotal = $this->normalizeNumericValue($matches[4]);

        if ($quantity <= 0 || $unitPrice < 0 || $lineTotal < 0 || $description === '') {
            return null;
        }

        if (preg_match('/\p{L}/u', $description) !== 1) {
            return null;
        }

        return [
            'product_id' => null,
            'product_code' => null,
            'product_name' => $description,
            'product_unit' => null,
            'description' => $description,
            'detail_1' => null,
            'detail_2' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'discount_type' => null,
            'discount_value' => null,
            'taxes' => [],
        ];
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<int, array<string, mixed>>  $items
     */
    private function estimateConfidence(array $headers, array $items): float
    {
        $score = 0.15;
        $score += min(0.35, count($headers) * 0.12);
        $score += min(0.45, count($items) * 0.15);

        return round(min(0.95, $score), 2);
    }

    private function normalizeDate(string $date): ?string
    {
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d'];

        foreach ($formats as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, $date)->format('Y-m-d');
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function normalizeNumericValue(string $value): float
    {
        $normalized = preg_replace('/[^\d,\.\-]/u', '', $value) ?? '0';

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $lastComma = mb_strrpos($normalized, ',');
            $lastDot = mb_strrpos($normalized, '.');

            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return (float) $normalized;
    }
}
