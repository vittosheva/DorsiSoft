<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Sri;

final class SriValidator
{
    public function clean(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }

    public function isValidRuc(string $ruc): bool
    {
        $cleanRuc = $this->clean($ruc);

        if (mb_strlen($cleanRuc) !== 13) {
            return false;
        }

        $provinceCode = (int) mb_substr($cleanRuc, 0, 2);
        if ($provinceCode < 1 || $provinceCode > 24) {
            return false;
        }

        $thirdDigit = (int) $cleanRuc[2];
        if ($thirdDigit < 0 || $thirdDigit > 9) {
            return false;
        }

        return mb_substr($cleanRuc, -3) !== '000';
    }
}
