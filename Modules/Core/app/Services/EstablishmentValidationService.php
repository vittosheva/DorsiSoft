<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Closure;
use Illuminate\Validation\ValidationException;

final class EstablishmentValidationService
{
    public function assertBusinessRules(array $rows, bool $requireExactlyOneMain = false): void
    {
        $error = $this->evaluate($rows, $requireExactlyOneMain);

        if ($error === null) {
            return;
        }

        throw ValidationException::withMessages([
            $error['attribute'] => $error['message'],
        ]);
    }

    public function makeRepeaterRule(): Closure
    {
        return fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
            $rows = is_array($value) ? $value : [];
            $error = $this->evaluate($rows, true);

            if ($error === null) {
                return;
            }

            $fail($error['message']);
        };
    }

    private function evaluate(array $rows, bool $requireExactlyOneMain): ?array
    {
        $mainCount = 0;
        $pairs = [];

        foreach ($rows as $index => $establishment) {
            if (($establishment['is_main'] ?? false) === true) {
                $mainCount++;
            }

            $name = mb_trim((string) ($establishment['name'] ?? ''));
            $nameSource = (string) ($establishment['name_source'] ?? 'manual');

            if ($nameSource === 'manual' && $name === '') {
                return [
                    'attribute' => "data.establishments.{$index}.name",
                    'message' => __('The establishment name is required for manually entered items (row :row).', ['row' => $index + 1]),
                ];
            }

            $establishmentCode = (string) ($establishment['establishment_code'] ?? '');
            $emissionPointCode = (string) ($establishment['emission_point_code'] ?? '');

            if ($establishmentCode === '' || $emissionPointCode === '') {
                continue;
            }

            $pair = $establishmentCode.'-'.$emissionPointCode;

            if (isset($pairs[$pair])) {
                return [
                    'attribute' => "data.establishments.{$index}.emission_point_code",
                    'message' => __('The establishment and emission point combination must be unique.'),
                ];
            }

            $pairs[$pair] = true;
        }

        if ($mainCount > 1) {
            return [
                'attribute' => 'data.establishments',
                'message' => __('Only one establishment can be marked as main.'),
            ];
        }

        if ($requireExactlyOneMain && count($rows) > 0 && $mainCount !== 1) {
            return [
                'attribute' => 'data.establishments',
                'message' => __('You must mark one establishment as main.'),
            ];
        }

        return null;
    }
}
