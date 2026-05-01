<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Withholdings\Pages;

use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithWithholdingHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\WithholdingResource;

final class EditWithholding extends BaseEditRecord
{
    use InteractsWithSalesDocumentHeaderActions;
    use InteractsWithWithholdingHeaderActions;

    protected static string $resource = WithholdingResource::class;

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            primaryActions: $this->getSalesDocumentPrimaryActions(extraActions: [$this->getViewSourceSettlementAction()]),
            electronicActions: [
                $this->getWithholdingIssueAction(),
                ...$this->getSalesDocumentElectronicActions(WithholdingResource::class),
            ],
            managementActions: $this->getSalesDocumentEditManagementActions(
                duplicateAction: $this->getWithholdingDuplicateAction(),
            ),
        );
    }
}
