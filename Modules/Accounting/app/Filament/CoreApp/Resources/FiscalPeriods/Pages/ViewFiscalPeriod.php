<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Pages;

use Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\FiscalPeriodResource;
use Modules\Core\Support\Pages\BaseViewRecord;

final class ViewFiscalPeriod extends BaseViewRecord
{
    protected static string $resource = FiscalPeriodResource::class;
}
