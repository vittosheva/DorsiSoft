<?php

declare(strict_types=1);

namespace Modules\Sales\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final class MoneyAmount implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        return $this->normalizeMoney($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string|float|null
    {
        return $this->normalizeMoney($value);
    }

    private function normalizeMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof FormattedMoney) {
            return $value->toFloat();
        }

        if (is_string($value)) {
            $value = str_replace(',', '', $value);
        }

        return (float) $value;
    }
}
