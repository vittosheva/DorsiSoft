<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Pages;

use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Sales\Enums\SalesOrderStatusEnum;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesOrderHeaderActions;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\SalesOrderResource;

final class EditSalesOrder extends BaseEditRecord
{
    use DispatchesItemsPersistEvent;
    use InteractsWithSalesDocumentHeaderActions;
    use InteractsWithSalesOrderHeaderActions;
    use SyncsDocumentItemsCount;

    protected static string $resource = SalesOrderResource::class;

    protected function getItemsPersistEvent(): string
    {
        return 'sales-order-items:persist';
    }

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            primaryActions: $this->getSalesDocumentPrimaryActions(extraActions: [
                $this->getSalesOrderConvertToInvoiceAction(),
                ...$this->getSalesOrderWorkflowActions(),
            ]),
            managementActions: $this->getSalesDocumentEditManagementActions(
                duplicateAction: $this->getSalesOrderDuplicateAction('duplicate')
                    ->hiddenLabel()
                    ->tooltip(__('Duplicate')),
                deleteAction: DeleteAction::make()
                    ->visible(fn () => $this->getRecord()->status === SalesOrderStatusEnum::Pending),
            ),
        );
    }
}
