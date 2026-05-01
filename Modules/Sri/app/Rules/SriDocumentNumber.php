<?php

declare(strict_types=1);

namespace Modules\Sri\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SriDocumentNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Must be string
        if (! is_string($value)) {
            $fail(__('The :attribute must be a valid string.'));

            return;
        }

        $parts = explode('-', $value);

        // Must have exactly 3 blocks
        if (count($parts) !== 3) {
            $fail(__('The :attribute format is invalid.'));

            return;
        }

        // Validate exact length: 3-3-9
        if (
            mb_strlen($parts[0]) !== 3 ||
            mb_strlen($parts[1]) !== 3 ||
            mb_strlen($parts[2]) !== 9
        ) {
            $fail(__('The :attribute format must be XXX-XXX-XXXXXXXXX.'));

            return;
        }

        foreach ($parts as $i => $part) {
            // Only digits
            if (! ctype_digit($part)) {
                $fail(__('Each block must contain only numbers.'));

                return;
            }
            // No block can be all zeros
            if ((int) $part === 0) {
                $fail(__('No block can be 000.'));

                return;
            }
        }

        // The sequential (last block) must be greater than 0
        if ((int) $parts[2] < 1) {
            $fail(__('The sequential must be greater than 0.'));

            return;
        }
    }
}
