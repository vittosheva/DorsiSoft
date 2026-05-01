<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Pages;

use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Sales\Enums\DebitNoteStatusEnum;
use Modules\Sales\Filament\Concerns\InteractsWithDebitNoteHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\DebitNoteResource;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Schemas\DebitNoteForm;

final class EditDebitNote extends BaseEditRecord
{
    use InteractsWithDebitNoteHeaderActions;
    use InteractsWithSalesDocumentHeaderActions;

    protected static string $resource = DebitNoteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return DebitNoteForm::normalizeFormDataForFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = DebitNoteForm::normalizeInvoiceReferenceData($data);

        return DebitNoteForm::normalizePaymentData($data);
    }

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            primaryActions: $this->getSalesDocumentPrimaryActions(),
            electronicActions: [
                $this->getDebitNoteIssueAction(),
                ...$this->getSalesDocumentElectronicActions(DebitNoteResource::class),
            ],
            managementActions: $this->getSalesDocumentEditManagementActions(
                duplicateAction: $this->getDebitNoteDuplicateAction('duplicate'),
                deleteAction: DeleteAction::make()
                    ->visible(fn () => $this->getRecord()->status === DebitNoteStatusEnum::Draft),
            ),
        );
    }
}
