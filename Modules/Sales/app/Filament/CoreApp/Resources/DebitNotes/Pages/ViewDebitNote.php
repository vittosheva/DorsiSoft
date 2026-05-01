<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Sales\Filament\Concerns\InteractsWithDebitNoteHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\DebitNoteResource;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Schemas\DebitNoteForm;
use Modules\Sri\Support\Concerns\InteractsWithElectronicAuditPanel;
use Modules\Sri\Support\Concerns\ShowsElectronicError;

final class ViewDebitNote extends BaseViewRecord
{
    use InteractsWithDebitNoteHeaderActions;
    use InteractsWithElectronicAuditPanel;
    use InteractsWithSalesDocumentHeaderActions;
    use ShowsElectronicError;

    protected static string $resource = DebitNoteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return DebitNoteForm::normalizeFormDataForFill($data);
    }

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
            primaryActions: $this->getSalesDocumentPrimaryActions(),
            electronicActions: [
                $this->getDebitNoteIssueAction(),
                ...$this->getSalesDocumentElectronicActions(DebitNoteResource::class),
            ],
            managementActions: $this->getSalesDocumentManagementActions(
                duplicateAction: $this->getDebitNoteDuplicateAction(),
            ),
        );
    }
}
