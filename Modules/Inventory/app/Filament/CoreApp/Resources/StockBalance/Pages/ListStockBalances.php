<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\StockBalance\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\StockBalance\StockBalanceResource;

final class ListStockBalances extends BaseListRecords
{
    protected static string $resource = StockBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
