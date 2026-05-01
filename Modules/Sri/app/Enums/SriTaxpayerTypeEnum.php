<?php

declare(strict_types=1);

namespace Modules\Sri\Enums;

enum SriTaxpayerTypeEnum: string
{
    case PERSONA_NATURAL = 'PERSONA NATURAL';
    case SOCIEDAD = 'SOCIEDAD';
    case SUCESION_INDIVISA = 'SUCESION INDIVISA';

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalizedValue = preg_replace('/\s+/', ' ', mb_trim($value));

        if ($normalizedValue === null || $normalizedValue === '') {
            return null;
        }

        $upperValue = mb_strtoupper($normalizedValue);

        if (str_contains($upperValue, 'PERSONA') && str_contains($upperValue, 'NATURAL')) {
            return self::PERSONA_NATURAL->value;
        }

        if (str_contains($upperValue, 'SOCIEDAD')) {
            return self::SOCIEDAD->value;
        }

        if (str_contains($upperValue, 'SUCESION') || str_contains($upperValue, 'SUCESIÓN')) {
            return self::SUCESION_INDIVISA->value;
        }

        return $normalizedValue;
    }
}
