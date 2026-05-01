<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\TaxWithholdingRuleResource;

final class CreateTaxWithholdingRule extends BaseCreateRecord
{
    protected static string $resource = TaxWithholdingRuleResource::class;
}
