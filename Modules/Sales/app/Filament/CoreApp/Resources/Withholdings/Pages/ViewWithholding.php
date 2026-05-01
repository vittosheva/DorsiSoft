<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Withholdings\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithWithholdingHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\WithholdingResource;
use Modules\Sri\Support\Concerns\InteractsWithElectronicAuditPanel;
use Modules\Sri\Support\Concerns\ShowsElectronicError;

final class ViewWithholding extends BaseViewRecord
{
    use InteractsWithElectronicAuditPanel;
    use InteractsWithSalesDocumentHeaderActions;
    use InteractsWithWithholdingHeaderActions;
    use ShowsElectronicError;

    protected static string $resource = WithholdingResource::class;

    protected function getFooterWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgetData(): array
    {
        return $this->getElectronicAuditWidgetData();
    }

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            approvalActions: $this->getWithholdingApprovalActions(),
            primaryActions: $this->getSalesDocumentPrimaryActions(extraActions: [$this->getViewSourceSettlementAction()]),
            electronicActions: [
                $this->getWithholdingIssueAction(),
                ...$this->getSalesDocumentElectronicActions(WithholdingResource::class),
            ],
            managementActions: $this->getSalesDocumentManagementActions(
                duplicateAction: $this->getWithholdingDuplicateAction(),
                extraActions: [$this->getWithholdingVoidAction()],
            ),
        );
    }
}
