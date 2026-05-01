<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Lots\Pages;

use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Lots\LotResource;

final class EditLot extends BaseEditRecord
{
    protected static string $resource = LotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn ($record) => $record->movements()->exists()),
        ];
    }
}
