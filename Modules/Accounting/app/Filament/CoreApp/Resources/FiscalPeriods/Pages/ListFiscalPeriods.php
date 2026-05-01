<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Pages;

use Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\FiscalPeriodResource;
use Modules\Core\Support\Pages\BaseListRecords;

final class ListFiscalPeriods extends BaseListRecords
{
    protected static string $resource = FiscalPeriodResource::class;
}
