<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\Concerns\SyncsSequentialNumberEvent;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\CreditNoteResource;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Schemas\CreditNoteForm;

final class CreateCreditNote extends BaseCreateRecord
{
    use DispatchesItemsPersistEvent;
    use SyncsDocumentItemsCount;
    use SyncsSequentialNumberEvent;

    protected static string $resource = CreditNoteResource::class;

    protected function getItemsPersistEvent(): string
    {
        return 'credit-note-items:persist';
    }

    protected function supportsSriPayments(): bool
    {
        return false;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = CreditNoteStatusEnum::Draft;
        $data['sri_payments'] = null;
        $data = CreditNoteForm::normalizeInvoiceReferenceData($data);

        return CreditNoteForm::normalizeReasonData($data);
    }
}
