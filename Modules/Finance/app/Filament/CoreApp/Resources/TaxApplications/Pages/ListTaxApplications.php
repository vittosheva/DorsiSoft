<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\TaxApplications\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Finance\Filament\CoreApp\Resources\TaxApplications\TaxApplicationResource;

final class ListTaxApplications extends BaseListRecords
{
    protected static string $resource = TaxApplicationResource::class;
}
