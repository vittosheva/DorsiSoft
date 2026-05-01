<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Modules\Sri\Enums\SriEmissionTypeEnum;
use Modules\Sri\Enums\SriEnvironmentEnum;
use Modules\Sri\Exceptions\AccessKeyException;

/**
 * Genera la Clave de Acceso SRI de 49 dígitos.
 *
 * Estructura:
 *   [ddMMyyyy: 8] + [tipoComprobante: 2] + [ruc: 13] + [ambiente: 1]
 *   + [serie: 6] + [secuencial: 9] + [codigoNumerico: 8] + [tipoEmision: 1] + [digitoVerificador: 1]
 *   = 49 dígitos
 *
 * @see Resolución NAC-DGERCGC14-00157
 */
final class AccessKeyGenerator
{
    /**
     * Genera una clave de acceso única para el comprobante.
     *
     * @param  string  $establishmentCode  3 dígitos, e.g. '001'
     * @param  string  $emissionPointCode  3 dígitos, e.g. '001'
     * @param  string  $sequentialNumber  9 dígitos, e.g. '000000001'
     * @param  string|null  $numericCode  8 dígitos aleatorios; se genera automáticamente si es null
     *
     * @throws AccessKeyException Si los parámetros no tienen el formato correcto
     */
    public function generate(
        Carbon|CarbonImmutable $date,
        string $documentTypeCode,
        string $ruc,
        SriEnvironmentEnum $environment,
        string $establishmentCode,
        string $emissionPointCode,
        string $sequentialNumber,
        ?string $numericCode = null,
        SriEmissionTypeEnum $emissionType = SriEmissionTypeEnum::Normal,
    ): string {
        $this->validateInputs($documentTypeCode, $ruc, $establishmentCode, $emissionPointCode, $sequentialNumber);

        $numericCode ??= mb_str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        $environmentCode = match ($environment) {
            SriEnvironmentEnum::TEST => '1',
            SriEnvironmentEnum::PRODUCTION => '2',
        };

        $key48 = implode('', [
            $date->format('dmY'),      // ddMMyyyy — 8 digits
            $documentTypeCode,         // 2 digits
            $ruc,                      // 13 digits
            $environmentCode,          // 1 digit
            $establishmentCode.$emissionPointCode, // 6 digits
            $sequentialNumber,         // 9 digits
            $numericCode,              // 8 digits
            $emissionType->sriCode(),  // 1 digit
        ]);

        if (mb_strlen($key48) !== 48) {
            throw new AccessKeyException(__('Key48 has wrong length: expected 48, got :length', ['length' => mb_strlen($key48)]));
        }

        $verificationDigit = $this->calculateVerificationDigit($key48);

        return $key48.$verificationDigit;
    }

    /**
     * Calcula el dígito verificador usando el algoritmo Módulo 11.
     * Si el resultado es 11 → 0, si es 10 → 1.
     */
    private function calculateVerificationDigit(string $key48): int
    {
        $weights = [2, 3, 4, 5, 6, 7];
        $sum = 0;
        $reversedKey = strrev($key48);

        for ($i = 0; $i < mb_strlen($reversedKey); $i++) {
            $digit = (int) $reversedKey[$i];
            $weight = $weights[$i % 6];
            $sum += $digit * $weight;
        }

        $remainder = $sum % 11;
        $result = 11 - $remainder;

        return match ($result) {
            11 => 0,
            10 => 1,
            default => $result,
        };
    }

    /** @throws AccessKeyException */
    private function validateInputs(
        string $documentTypeCode,
        string $ruc,
        string $establishmentCode,
        string $emissionPointCode,
        string $sequentialNumber,
    ): void {
        if (! preg_match('/^\d{2}$/', $documentTypeCode)) {
            throw new AccessKeyException("documentTypeCode must be exactly 2 digits, got: '{$documentTypeCode}'");
        }

        if (! preg_match('/^\d{13}$/', $ruc)) {
            throw new AccessKeyException("RUC must be exactly 13 digits, got: '{$ruc}'");
        }

        if (! preg_match('/^\d{3}$/', $establishmentCode)) {
            throw new AccessKeyException("establishmentCode must be exactly 3 digits, got: '{$establishmentCode}'");
        }

        if (! preg_match('/^\d{3}$/', $emissionPointCode)) {
            throw new AccessKeyException("emissionPointCode must be exactly 3 digits, got: '{$emissionPointCode}'");
        }

        if (! preg_match('/^\d{9}$/', $sequentialNumber)) {
            throw new AccessKeyException("sequentialNumber must be exactly 9 digits, got: '{$sequentialNumber}'");
        }
    }
}
