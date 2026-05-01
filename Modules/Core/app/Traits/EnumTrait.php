<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

trait EnumTrait
{
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }

    public static function toArray(): array
    {
        return array_combine(self::names(), self::values());
    }

    public static function toFilamentEnum(): array
    {
        return array_combine(self::values(), self::values());
    }

    public static function except(array $excludedValues): array
    {
        $excludedValues = array_map(fn ($value) => $value instanceof self ? $value->value : $value, $excludedValues);
        $filteredValues = array_diff(self::values(), $excludedValues);

        $translatedArray = self::arrayTranslate();

        return array_intersect_key($translatedArray, array_flip($filteredValues));
    }

    public static function only(array $includedValues): array
    {
        $includedValues = array_map(fn ($value) => $value instanceof self ? $value->value : $value, $includedValues);
        $filteredValues = array_intersect(self::values(), $includedValues);
        $translatedArray = self::arrayTranslate();

        return array_intersect_key($translatedArray, array_flip($filteredValues));
    }

    public static function arrayTranslateWithKeys(?string $method = null): array
    {
        $collection = collect(
            $method !== null ? self::$method() : self::values(),
        );

        return array_combine(
            $collection
                ->map(fn ($key) => static::tryFrom($key)->value)
                ->toArray(),
            $collection
                ->map(fn ($key) => static::tryFrom($key)->translate())
                ->toArray(),
        );
    }

    public static function arrayTranslate(): array
    {
        return array_combine(
            self::values(),
            collect(self::values())
                ->map(fn ($key) => static::tryFrom($key)->translate())
                ->toArray(),
        );
    }

    public static function sorted(): array
    {
        return collect(self::cases())
            ->sortBy(fn ($case) => $case->getLabel())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->all();
    }

    public static function toObjectArray(): array
    {
        return array_map(
            fn (self $case) => (object) [
                'name' => $case->name,
                'value' => $case->value,
            ],
            self::cases(),
        );
    }

    public static function checkAndConvert($data): ?self
    {
        if ($data instanceof self) {
            return $data;
        }

        if (is_string($data)) {
            return self::tryFrom($data);
        }

        if (is_int($data)) {
            return self::from($data);
        }

        return null;
    }
}
