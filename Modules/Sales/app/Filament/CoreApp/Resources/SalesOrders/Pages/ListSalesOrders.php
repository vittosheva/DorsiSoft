<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\SalesOrderResource;

final class ListSalesOrders extends BaseListRecords
{
    protected static string $resource = SalesOrderResource::class;
}
