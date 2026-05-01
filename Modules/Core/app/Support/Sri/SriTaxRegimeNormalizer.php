<?php

declare(strict_types=1);

namespace Modules\Core\Support\Sri;

use Modules\Sri\Enums\SriRegimeTypeEnum;

final class SriTaxRegimeNormalizer
{
    public function normalize(?string $value): ?string
    {
        if ($value === null || mb_trim($value) === '') {
            return null;
        }

        $normalizedValue = mb_strtoupper(mb_trim($value));

        if (in_array($normalizedValue, ['GENERAL', 'REGIMEN GENERAL', 'RÉGIMEN GENERAL'], true)) {
            return SriRegimeTypeEnum::GENERAL->value;
        }

        if (str_contains($normalizedValue, 'RIMPE') && str_contains($normalizedValue, 'POPULAR')) {
            return SriRegimeTypeEnum::RIMPE_NEGOCIO_POPULAR->value;
        }

        if (str_contains($normalizedValue, 'RIMPE')) {
            return SriRegimeTypeEnum::RIMPE_EMPRENDEDOR->value;
        }

        return null;
    }
}
