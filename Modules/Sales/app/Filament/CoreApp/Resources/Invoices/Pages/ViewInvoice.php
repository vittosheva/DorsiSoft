<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Sales\Filament\Concerns\InteractsWithInvoiceHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sri\Support\Concerns\InteractsWithElectronicAuditPanel;
use Modules\Sri\Support\Concerns\ShowsElectronicError;

final class ViewInvoice extends BaseViewRecord
{
    use InteractsWithElectronicAuditPanel;
    use InteractsWithInvoiceHeaderActions;
    use InteractsWithSalesDocumentHeaderActions;
    use ShowsElectronicError;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderWidgetData(): array
    {
        return $this->getElectronicAuditWidgetData();
    }

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            approvalActions: $this->getInvoiceApprovalActions(),
            primaryActions: $this->getSalesDocumentPrimaryActions(),
            electronicActions: [
                $this->getInvoiceIssueAction(),
                ...$this->getSalesDocumentElectronicActions(InvoiceResource::class),
            ],
            managementActions: $this->getSalesDocumentManagementActions(
                duplicateAction: $this->getInvoiceDuplicateAction(),
            ),
        );
    }
}
