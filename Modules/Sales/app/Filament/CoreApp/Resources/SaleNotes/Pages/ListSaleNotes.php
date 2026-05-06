<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\SaleNoteResource;

final class ListSaleNotes extends ListRecords
{
    protected static string $resource = SaleNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
