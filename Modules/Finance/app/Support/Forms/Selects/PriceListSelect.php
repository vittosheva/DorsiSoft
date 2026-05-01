<?php

declare(strict_types=1);

namespace Modules\Finance\Support\Forms\Selects;

use Filament\Forms\Components\Select;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\PriceListResource;
use Modules\Finance\Models\PriceList;

final class PriceListSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->live()
            ->options(fn (): array => PriceList::query()->active()->pluck('name', 'id')->all())
            ->nullable()
            ->searchable()
            ->preload()
            ->prefixIcon(PriceListResource::getNavigationIcon());
    }

    public static function getDefaultName(): ?string
    {
        return 'price_list_id';
    }
}
