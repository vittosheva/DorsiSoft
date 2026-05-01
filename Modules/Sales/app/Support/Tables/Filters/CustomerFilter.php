<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;

final class CustomerFilter extends SelectFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Customer'))
            ->relationship(
                'businessPartner',
                'legal_name',
                fn ($query) => $query
                    ->customers()
                    ->select(['id', 'legal_name'])
                    ->orderBy('legal_name')
                    ->limit(config('dorsi.filament.select_filter_options_limit', 50)),
            )
            ->searchable()
            ->preload()
            ->columnSpan(4);
    }

    public static function getDefaultName(): ?string
    {
        return 'customer';
    }
}
