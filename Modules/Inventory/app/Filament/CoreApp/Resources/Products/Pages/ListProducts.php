<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Products\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\Products\ProductResource;

final class ListProducts extends BaseListRecords
{
    protected static string $resource = ProductResource::class;
}
