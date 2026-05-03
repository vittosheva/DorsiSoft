<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\InteractsWithCreditNoteHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\Concerns\SyncsSequentialNumberEvent;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\CreditNoteResource;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Schemas\CreditNoteForm;

final class EditCreditNote extends BaseEditRecord
{
    use DispatchesItemsPersistEvent;
    use InteractsWithCreditNoteHeaderActions;
    use InteractsWithSalesDocumentHeaderActions;
    use SyncsDocumentItemsCount;
    use SyncsSequentialNumberEvent;

    protected static string $resource = CreditNoteResource::class;

    public function form(Schema $schema): Schema
    {
        return CreditNoteForm::configure($schema);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['sri_payments']);

        return $data;
    }

    protected function getItemsPersistEvent(): string
    {
        return 'credit-note-items:persist';
    }

    protected function supportsSriPayments(): bool
    {
        return false;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['sri_payments'] = null;
        $data = CreditNoteForm::normalizeInvoiceReferenceData($data);

        return CreditNoteForm::normalizeReasonData($data);
    }

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            primaryActions: $this->getSalesDocumentPrimaryActions(),
            electronicActions: [
                $this->getCreditNoteIssueAction(),
                ...$this->getSalesDocumentElectronicActions(CreditNoteResource::class),
            ],
            managementActions: $this->getSalesDocumentEditManagementActions(
                duplicateAction: $this->getCreditNoteDuplicateAction('duplicate'),
                deleteAction: DeleteAction::make()
                    ->visible(fn () => $this->getRecord()->status === CreditNoteStatusEnum::Draft),
            ),
        );
    }
}
