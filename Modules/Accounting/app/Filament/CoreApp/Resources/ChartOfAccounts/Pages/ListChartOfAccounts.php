<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\Pages;

use Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\ChartOfAccountResource;
use Modules\Core\Support\Pages\BaseListRecords;

final class ListChartOfAccounts extends BaseListRecords
{
    protected static string $resource = ChartOfAccountResource::class;
}
