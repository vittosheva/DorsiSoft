<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Warehouses\Pages;

use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Warehouses\WarehouseResource;

final class EditWarehouse extends BaseEditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn ($record) => $record->movements()->exists()),
        ];
    }
}
