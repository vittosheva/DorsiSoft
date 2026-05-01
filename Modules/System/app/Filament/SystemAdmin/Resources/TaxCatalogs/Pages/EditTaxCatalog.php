<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\TaxCatalogResource;

final class EditTaxCatalog extends BaseEditRecord
{
    protected static string $resource = TaxCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DeleteAction::make(),
        ];
    }
}
