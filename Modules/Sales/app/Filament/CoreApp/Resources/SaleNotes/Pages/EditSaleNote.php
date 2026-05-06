<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Sales\Enums\SaleNoteStatusEnum;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\SaleNoteResource;

final class EditSaleNote extends EditRecord
{
    protected static string $resource = SaleNoteResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->status !== SaleNoteStatusEnum::Draft) {
            $this->halt();
        }

        return $data;
    }
}
