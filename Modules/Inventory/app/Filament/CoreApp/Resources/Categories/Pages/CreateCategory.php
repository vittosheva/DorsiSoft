<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Categories\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\CategoryResource;

final class CreateCategory extends BaseCreateRecord
{
    protected static string $resource = CategoryResource::class;
}
