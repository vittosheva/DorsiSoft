<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\SaleNoteResource;

final class CreateSaleNote extends BaseCreateRecord
{
    use DispatchesItemsPersistEvent;
    use SyncsDocumentItemsCount;

    protected static string $resource = SaleNoteResource::class;

    protected function getItemsPersistEvent(): string
    {
        return 'sale-note-items:persist';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'draft';

        return $data;
    }
}
