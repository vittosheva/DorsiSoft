<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\SriCatalogs\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\System\Filament\SystemAdmin\Resources\SriCatalogs\SriCatalogResource;

final class ListSriCatalogs extends BaseListRecords
{
    protected static string $resource = SriCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
