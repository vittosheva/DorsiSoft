<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Withholdings\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\WithholdingResource;

final class ListWithholdings extends BaseListRecords
{
    protected static string $resource = WithholdingResource::class;
}
