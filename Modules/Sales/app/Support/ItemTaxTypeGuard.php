<?php

declare(strict_types=1);

namespace Modules\Sales\Support;

use BackedEnum;

final class ItemTaxTypeGuard
{
    /**
     * @param  iterable<mixed>  $taxes
     * @return list<string>
     */
    public function duplicateTypes(iterable $taxes): array
    {
        $seenTypes = [];
        $duplicateTypes = [];

        foreach ($taxes as $tax) {
            $type = $this->extractType($tax);

            if ($type === null) {
                continue;
            }

            if (isset($seenTypes[$type])) {
                $duplicateTypes[$type] = $type;

                continue;
            }

            $seenTypes[$type] = true;
        }

        return array_values($duplicateTypes);
    }

    /**
     * @param  iterable<mixed>  $taxes
     */
    public function containsType(iterable $taxes, mixed $type): bool
    {
        $normalizedType = $this->normalizeType($type);

        if ($normalizedType === null) {
            return false;
        }

        foreach ($taxes as $tax) {
            if ($this->extractType($tax) === $normalizedType) {
                return true;
            }
        }

        return false;
    }

    public function normalizeType(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value === null) {
            return null;
        }

        $normalizedValue = mb_strtoupper(mb_trim((string) $value));

        return $normalizedValue !== '' ? $normalizedValue : null;
    }

    private function extractType(mixed $tax): ?string
    {
        if (is_array($tax)) {
            return $this->normalizeType($tax['tax_type'] ?? $tax['type'] ?? null);
        }

        if (is_object($tax)) {
            return $this->normalizeType($tax->tax_type ?? $tax->type ?? null);
        }

        return null;
    }
}
