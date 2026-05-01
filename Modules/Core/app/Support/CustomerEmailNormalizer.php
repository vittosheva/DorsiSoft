<?php

declare(strict_types=1);

namespace Modules\Core\Support;

final class CustomerEmailNormalizer
{
    public static function normalizeForCast(mixed $value, mixed $cast): array|string|null
    {
        return in_array($cast, ['array', 'json'], true)
            ? self::normalizeAsArray($value)
            : self::normalizeAsString($value);
    }

    public static function normalizeAsString(mixed $value): ?string
    {
        if (is_array($value)) {
            foreach ($value as $email) {
                $normalizedEmail = self::normalizeScalar($email);

                if ($normalizedEmail !== null) {
                    return $normalizedEmail;
                }
            }

            return null;
        }

        return self::normalizeScalar($value);
    }

    /**
     * @return list<string>|null
     */
    public static function normalizeAsArray(mixed $value): ?array
    {
        $emails = is_array($value) ? $value : [$value];
        $normalizedEmails = array_values(array_filter(array_map(
            fn (mixed $email): ?string => self::normalizeScalar($email),
            $emails,
        )));

        return $normalizedEmails !== [] ? $normalizedEmails : null;
    }

    private static function normalizeScalar(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $email = mb_trim($value);

        return $email !== '' ? $email : null;
    }
}
