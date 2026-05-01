<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Warehouses\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\Warehouses\WarehouseResource;

final class ListWarehouses extends BaseListRecords
{
    protected static string $resource = WarehouseResource::class;
}
