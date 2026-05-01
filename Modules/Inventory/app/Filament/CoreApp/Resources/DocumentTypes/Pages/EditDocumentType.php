<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\Pages;

use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\InventoryDocumentTypeResource;

final class EditDocumentType extends BaseEditRecord
{
    protected static string $resource = InventoryDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn ($record) => $record->movements()->exists()),
        ];
    }
}
