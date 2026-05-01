<?php

declare(strict_types=1);

namespace Modules\Core\Support\Sri;

use Closure;

final class SriPayloadMapper
{
    public function __construct(private SriTaxRegimeNormalizer $taxRegimeNormalizer) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    public function extractStringValue(array $data, array $keys): ?string
    {
        $value = $this->extractValue($data, $keys);

        if (is_string($value) && mb_trim($value) !== '') {
            return mb_trim($value);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    public function extractBooleanValue(array $data, array $keys): ?bool
    {
        $value = $this->extractValue($data, $keys);

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalizedValue = mb_strtoupper(mb_trim($value));

        if (in_array($normalizedValue, ['SI', 'SÍ', 'YES', 'TRUE', '1'], true)) {
            return true;
        }

        if (in_array($normalizedValue, ['NO', 'FALSE', '0'], true)) {
            return false;
        }

        return null;
    }

    public function normalizeTaxRegime(?string $value): ?string
    {
        return $this->taxRegimeNormalizer->normalize($value);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     * @return array<int|string, mixed>|null
     */
    public function extractArrayValue(array $data, array $keys): ?array
    {
        $value = $this->extractValue($data, $keys);

        if (! is_array($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>|array<string, array<string, mixed>>
     */
    public function mapEstablishments(array $rows, ?Closure $keyResolver = null): array
    {
        $mapped = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $establishmentCode = $this->extractStringValue($row, [
                'numeroEstablecimiento',
                'numero_establecimiento',
                'establishment_code',
            ]);

            if ($establishmentCode === null) {
                continue;
            }

            $isMain = $this->extractBooleanValue($row, ['matriz']) ?? false;
            $name = $this->extractStringValue($row, ['nombreFantasiaComercial', 'nombre_fantasia_comercial', 'name']);

            if ($name === null) {
                $name = 'N/A';
            }

            $item = [
                'establishment_code' => mb_str_pad(preg_replace('/\D/', '', $establishmentCode) ?? '', 3, '0', STR_PAD_LEFT),
                'emission_point_code' => '001',
                'name' => $name,
                'name_source' => $name === 'N/A' ? 'fallback' : 'sri',
                'address' => $this->extractStringValue($row, ['direccionCompleta', 'direccion_completa', 'address']),
                'is_main' => $isMain,
                'is_active' => $this->extractStringValue($row, ['estado']) === 'ABIERTO',
                'show_more_fields' => false,
            ];

            if ($keyResolver instanceof Closure) {
                $mapped[(string) $keyResolver($item, $index, $row)] = $item;

                continue;
            }

            $mapped[] = $item;
        }

        if ($mapped === []) {
            return [];
        }

        if (! collect($mapped)->contains(fn (array $item): bool => ($item['is_main'] ?? false) === true)) {
            $firstKey = array_key_first($mapped);

            if ($firstKey !== null) {
                $mapped[$firstKey]['is_main'] = true;
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    private function extractValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($data, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }

            $foundValue = $this->findValueByKey($data, $key);

            if ($foundValue !== null && $foundValue !== '') {
                return $foundValue;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findValueByKey(array $data, string $searchKey): mixed
    {
        $normalizedSearchKey = $this->normalizeKey($searchKey);

        foreach ($data as $key => $value) {
            if (is_string($key) && $this->normalizeKey($key) === $normalizedSearchKey) {
                return $value;
            }

            if (is_array($value)) {
                $nestedValue = $this->findValueByKey($value, $searchKey);

                if ($nestedValue !== null && $nestedValue !== '') {
                    return $nestedValue;
                }
            }
        }

        return null;
    }

    private function normalizeKey(string $key): string
    {
        return mb_strtolower(preg_replace('/[^a-z0-9]/', '', $key) ?? '');
    }
}
