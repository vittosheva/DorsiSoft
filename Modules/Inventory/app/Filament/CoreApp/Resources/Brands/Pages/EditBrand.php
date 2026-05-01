<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Brands\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\BrandResource;

final class EditBrand extends BaseEditRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DeleteAction::make(),
        ];
    }
}
