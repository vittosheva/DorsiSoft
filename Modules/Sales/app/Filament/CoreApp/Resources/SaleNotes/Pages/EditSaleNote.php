<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages;

use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Sales\Enums\SaleNoteStatusEnum;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\SaleNoteResource;

final class EditSaleNote extends BaseEditRecord
{
    use DispatchesItemsPersistEvent;
    use SyncsDocumentItemsCount;

    protected static string $resource = SaleNoteResource::class;

    protected function getItemsPersistEvent(): string
    {
        return 'sale-note-items:persist';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->status !== SaleNoteStatusEnum::Draft) {
            $this->halt();
        }

        if ($this->record->items()->count() > 0 && $data['warehouse_id'] !== $this->record->warehouse_id) {
            $this->addError('data.warehouse_id', __('Cannot change warehouse once items have been added to the document.'));
        }

        return $data;
    }
}
