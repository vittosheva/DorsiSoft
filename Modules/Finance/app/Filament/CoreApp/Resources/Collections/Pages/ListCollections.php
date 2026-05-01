<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Collections\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Finance\Filament\CoreApp\Resources\Collections\CollectionResource;

final class ListCollections extends BaseListRecords
{
    protected static string $resource = CollectionResource::class;
}
