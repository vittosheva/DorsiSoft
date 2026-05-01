<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Products\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Products\ProductResource;

final class EditProduct extends BaseEditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DeleteAction::make(),
        ];
    }
}
