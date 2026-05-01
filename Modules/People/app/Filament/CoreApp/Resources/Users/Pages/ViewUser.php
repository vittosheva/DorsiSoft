<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\Users\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\People\Filament\CoreApp\Resources\Users\UserResource;

final class ViewUser extends BaseViewRecord
{
    protected static string $resource = UserResource::class;
}
