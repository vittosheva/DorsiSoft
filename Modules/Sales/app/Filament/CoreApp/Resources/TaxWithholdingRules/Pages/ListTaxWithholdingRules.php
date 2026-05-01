<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\TaxWithholdingRuleResource;

final class ListTaxWithholdingRules extends BaseListRecords
{
    protected static string $resource = TaxWithholdingRuleResource::class;
}
