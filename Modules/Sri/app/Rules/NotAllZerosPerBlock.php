<?php

declare(strict_types=1);

namespace Modules\Sri\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class NotAllZerosPerBlock implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Debe ser string
        if (! is_string($value)) {
            $fail(__('The :attribute must be a valid string.'));

            return;
        }

        $parts = explode('-', $value);

        // Debe tener exactamente 3 bloques
        if (count($parts) !== 3) {
            $fail(__('The :attribute format is invalid.'));

            return;
        }

        // Validar longitud exacta: 3-3-9
        if (
            mb_strlen($parts[0]) !== 3 ||
            mb_strlen($parts[1]) !== 3 ||
            mb_strlen($parts[2]) !== 9
        ) {
            $fail(__('The :attribute format must be XXX-XXX-XXXXXXXXX.'));

            return;
        }

        foreach ($parts as $part) {
            // Solo números
            if (! ctype_digit($part)) {
                $fail(__('Each block must contain only numbers.'));

                return;
            }

            // ❌ No permitir bloques completamente en cero
            if ((int) $part === 0) {
                $fail(__('Each block must contain at least one non-zero digit.'));

                return;
            }
        }
    }
}
