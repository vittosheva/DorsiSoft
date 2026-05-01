<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\Pages;

use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\TaxWithholdingRuleResource;

final class EditTaxWithholdingRule extends BaseEditRecord
{
    protected static string $resource = TaxWithholdingRuleResource::class;
}
