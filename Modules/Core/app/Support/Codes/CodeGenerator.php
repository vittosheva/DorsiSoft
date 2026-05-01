<?php

declare(strict_types=1);

namespace Modules\Core\Support\Codes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CodeGenerator
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $scope
     */
    public static function next(string $modelClass, string $prefix, int $padding = 3, array $scope = [], string $column = 'code'): string
    {
        $normalizedPrefix = CodeFormatter::normalize($prefix) ?? '';
        $padding = max(1, $padding);

        /** @var Model $modelClass */
        $query = in_array(SoftDeletes::class, class_uses_recursive($modelClass))
            ? $modelClass::withTrashed()
            : $modelClass::query();

        foreach ($scope as $scopeColumn => $scopeValue) {
            if ($scopeValue === null) {
                continue;
            }

            $query->where($scopeColumn, $scopeValue);
        }

        if ($normalizedPrefix !== '') {
            $query->where($column, 'like', $normalizedPrefix.'%');
        }

        $codes = $query
            ->select([$column])
            ->orderByDesc($column)
            ->limit(100)
            ->pluck($column)
            ->filter(fn (mixed $value): bool => is_string($value))
            ->all();

        $nextNumber = 1;

        foreach ($codes as $code) {
            $normalizedCode = CodeFormatter::normalize($code) ?? '';

            if ($normalizedPrefix !== '' && ! str_starts_with($normalizedCode, $normalizedPrefix)) {
                continue;
            }

            $numericPart = mb_substr($normalizedCode, mb_strlen($normalizedPrefix));

            if ($numericPart === '' || ! ctype_digit($numericPart)) {
                continue;
            }

            $nextNumber = max($nextNumber, ((int) $numericPart) + 1);
        }

        return $normalizedPrefix.mb_str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);
    }
}
