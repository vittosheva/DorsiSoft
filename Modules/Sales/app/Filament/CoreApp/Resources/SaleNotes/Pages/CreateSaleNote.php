<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\SaleNoteResource;

final class CreateSaleNote extends CreateRecord
{
    protected static string $resource = SaleNoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'draft';

        return $data;
    }
}
