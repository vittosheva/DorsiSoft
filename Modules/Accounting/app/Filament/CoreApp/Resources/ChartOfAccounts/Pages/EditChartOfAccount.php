<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\Pages;

use Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\ChartOfAccountResource;
use Modules\Core\Support\Pages\BaseEditRecord;

final class EditChartOfAccount extends BaseEditRecord
{
    protected static string $resource = ChartOfAccountResource::class;
}
