<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesOrderHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\SalesOrderResource;
use Modules\Workflow\Filament\CoreApp\Widgets\ApprovalHistoryWidget;

final class ViewSalesOrder extends BaseViewRecord
{
    use InteractsWithSalesDocumentHeaderActions;
    use InteractsWithSalesOrderHeaderActions;

    protected static string $resource = SalesOrderResource::class;

    protected function getFooterWidgets(): array
    {
        return [ApprovalHistoryWidget::class];
    }

    protected function getFooterWidgetData(): array
    {
        return ['record' => $this->getRecord()];
    }

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            approvalActions: $this->getSalesOrderApprovalActions(),
            primaryActions: $this->getSalesDocumentPrimaryActions(extraActions: [
                $this->getSalesOrderConvertToInvoiceAction(),
                ...$this->getSalesOrderWorkflowActions(),
            ]),
            managementActions: $this->getSalesDocumentManagementActions(
                duplicateAction: $this->getSalesOrderDuplicateAction(),
            ),
        );
    }
}
