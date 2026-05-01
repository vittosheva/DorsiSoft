<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\StockBalance\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\StockBalance\StockBalanceResource;

final class ListStockBalances extends ListRecords
{
    protected static string $resource = StockBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
