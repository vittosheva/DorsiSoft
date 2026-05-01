<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\InventoryDocumentTypeResource;

final class CreateDocumentType extends BaseCreateRecord
{
    protected static string $resource = InventoryDocumentTypeResource::class;
}
