<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Pages;

use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\SalesOrderResource;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Schemas\SalesOrderForm;

final class CreateSalesOrder extends BaseCreateRecord
{
    use DispatchesItemsPersistEvent;
    use SyncsDocumentItemsCount;

    protected static string $resource = SalesOrderResource::class;

    public function form(Schema $schema): Schema
    {
        return SalesOrderForm::configure($schema);
    }

    protected function getItemsPersistEvent(): string
    {
        return 'sales-order-items:persist';
    }
}
