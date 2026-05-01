<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Filters;

use Zvizvi\UserFields\Components\UserSelectFilter;

final class CreatorFilter extends UserSelectFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->relationship(
                'creator',
                'name',
                fn ($query) => $query
                    ->select(['id', 'name', 'avatar_url'])
                    ->orderBy('name')
                    ->limit(config('dorsi.filament.select_filter_options_limit', 50)),
            )
            ->searchable()
            ->preload();
    }

    public static function getDefaultName(): ?string
    {
        return 'creator';
    }
}
