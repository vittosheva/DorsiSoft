<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Taxes\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Finance\Filament\CoreApp\Resources\Taxes\TaxResource;

final class ListTaxes extends BaseListRecords
{
    protected static string $resource = TaxResource::class;
}
