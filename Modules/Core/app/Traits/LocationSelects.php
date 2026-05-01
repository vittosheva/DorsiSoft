<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

trait LocationSelects
{
    private static function configureLocationQuery(Builder $query): Builder
    {
        return $query
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit(config('dorsi.filament.select_filter_options_limit', 50));
    }

    private static function configureDependentLocationQuery(Builder $query, Get $get, string $parentField, string $parentColumn): Builder
    {
        return self::configureLocationQuery(
            $query->when(
                $get($parentField),
                fn (Builder $dependent, $parentValue) => $dependent->where($parentColumn, $parentValue),
                fn (Builder $dependent) => $dependent->whereRaw('1 = 0')
            )
        );
    }
}
