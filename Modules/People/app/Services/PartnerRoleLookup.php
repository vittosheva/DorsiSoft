<?php

declare(strict_types=1);

namespace Modules\People\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\People\Models\PartnerRole;

final class PartnerRoleLookup
{
    public function idFor(string $code): ?int
    {
        if ($code === '') {
            return null;
        }

        $cacheKey = $this->cacheKey($code);
        $ttl = now()->addHours(24);
        $value = Cache::remember($cacheKey, $ttl, fn (): ?int => $this->resolveValue($code));
        $normalizedValue = $this->normalizeCachedValue($value);

        if ($normalizedValue === null) {
            Cache::forget($cacheKey);

            return null;
        }

        if ($normalizedValue !== $value) {
            Cache::put($cacheKey, $normalizedValue, $ttl);
        }

        return $normalizedValue;
    }

    public function idsFor(array $codes): Collection
    {
        return collect($codes)
            ->map(fn (string $code) => $this->idFor($code))
            ->filter()
            ->values();
    }

    public function forget(string $code): void
    {
        if ($code === '') {
            return;
        }

        Cache::forget($this->cacheKey($code));
    }

    private function cacheKey(string $code): string
    {
        return "partner_role_id.{$code}";
    }

    private function resolveValue(string $code): ?int
    {
        // ->value('id') returns int|null directly; normalizeCachedValue handles
        // any unexpected type that may arrive from a non-standard cache driver.
        return PartnerRole::query()
            ->where('code', $code)
            ->value('id');
    }

    private function normalizeCachedValue(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}
