<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;
use Modules\People\Enums\RoleEnum;

final class SellerFilter extends SelectFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Seller'))
            ->relationship(
                'seller',
                'name',
                fn ($query) => $query
                    ->role(RoleEnum::SALES_REP->value)
                    ->select(['id', 'name'])
                    ->orderBy('name')
                    ->limit(config('dorsi.filament.select_filter_options_limit', 50)),
            )
            ->searchable()
            ->preload();
    }

    public static function getDefaultName(): ?string
    {
        return 'seller';
    }
}
