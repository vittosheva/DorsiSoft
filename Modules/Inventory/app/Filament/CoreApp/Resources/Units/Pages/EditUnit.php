<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Units\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Units\UnitResource;

final class EditUnit extends BaseEditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DeleteAction::make(),
        ];
    }
}
