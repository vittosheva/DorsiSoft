<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sales\Enums\DebitNoteStatusEnum;
use Modules\Sales\Filament\Concerns\SyncsSequentialNumberEvent;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\DebitNoteResource;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Schemas\DebitNoteForm;

final class CreateDebitNote extends BaseCreateRecord
{
    use SyncsSequentialNumberEvent;

    protected static string $resource = DebitNoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = DebitNoteStatusEnum::Draft;
        $data = DebitNoteForm::normalizeInvoiceReferenceData($data);

        return DebitNoteForm::normalizePaymentData($data);
    }
}
