<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Resources\EstablishmentResource\Pages;

use Modules\Core\Filament\CoreApp\Resources\EstablishmentResource;
use Modules\Core\Support\Pages\BaseListRecords;

final class ListEstablishments extends BaseListRecords
{
    protected static string $resource = EstablishmentResource::class;
}
