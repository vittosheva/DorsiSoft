<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\SerialNumbers\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\SerialNumbers\SerialNumberResource;

final class ListSerialNumbers extends BaseListRecords
{
    protected static string $resource = SerialNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
