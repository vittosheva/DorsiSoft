<?php

declare(strict_types=1);

namespace Modules\Core\Support\Codes;

use Illuminate\Database\Eloquent\Model;

final class YearlyCodeGenerator
{
    /**
     * Generate the next code with a yearly prefix format: PREFIX-YEAR-NNNNNN
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $scope
     */
    public static function next(string $modelClass, string $prefix, int $year, int $padding = 6, array $scope = [], string $column = 'code'): string
    {
        $yearlyPrefix = mb_strtoupper(mb_trim($prefix)).'-'.$year.'-';

        return CodeGenerator::next(
            modelClass: $modelClass,
            prefix: $yearlyPrefix,
            padding: $padding,
            scope: $scope,
            column: $column,
        );
    }
}
