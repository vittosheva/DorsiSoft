<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\InventoryDocumentTypeResource;

final class ListDocumentTypes extends BaseListRecords
{
    protected static string $resource = InventoryDocumentTypeResource::class;
}
