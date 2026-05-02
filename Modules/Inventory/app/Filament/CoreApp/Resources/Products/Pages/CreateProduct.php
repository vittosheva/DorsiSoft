<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Products\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Products\ProductResource;

final class CreateProduct extends BaseCreateRecord
{
    protected static string $resource = ProductResource::class;
}
