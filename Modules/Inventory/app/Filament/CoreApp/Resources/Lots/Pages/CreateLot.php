<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Lots\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Lots\LotResource;

final class CreateLot extends BaseCreateRecord
{
    protected static string $resource = LotResource::class;
}
