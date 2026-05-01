<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Warehouses\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Warehouses\WarehouseResource;

final class CreateWarehouse extends BaseCreateRecord
{
    protected static string $resource = WarehouseResource::class;
}
