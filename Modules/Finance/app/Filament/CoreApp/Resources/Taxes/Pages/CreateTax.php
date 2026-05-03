<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Taxes\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Finance\Filament\CoreApp\Resources\Taxes\TaxResource;

final class CreateTax extends BaseCreateRecord
{
    protected static string $resource = TaxResource::class;
}
