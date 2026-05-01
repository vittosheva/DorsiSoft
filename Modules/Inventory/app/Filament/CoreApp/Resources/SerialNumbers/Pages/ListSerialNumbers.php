<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\SerialNumbers\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\SerialNumbers\SerialNumberResource;

final class ListSerialNumbers extends ListRecords
{
    protected static string $resource = SerialNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
