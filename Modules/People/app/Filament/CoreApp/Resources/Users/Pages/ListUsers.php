<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\Users\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\People\Filament\CoreApp\Resources\Users\UserResource;

final class ListUsers extends BaseListRecords
{
    protected static string $resource = UserResource::class;
}
