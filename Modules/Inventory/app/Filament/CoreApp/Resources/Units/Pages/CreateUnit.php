<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Units\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Units\UnitResource;

final class CreateUnit extends BaseCreateRecord
{
    protected static string $resource = UnitResource::class;
}
