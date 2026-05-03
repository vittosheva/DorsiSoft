<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\SalesOrderResource;

final class CreateSalesOrder extends BaseCreateRecord
{
    use DispatchesItemsPersistEvent;
    use SyncsDocumentItemsCount;

    protected static string $resource = SalesOrderResource::class;

    protected function getItemsPersistEvent(): string
    {
        return 'sales-order-items:persist';
    }
}
