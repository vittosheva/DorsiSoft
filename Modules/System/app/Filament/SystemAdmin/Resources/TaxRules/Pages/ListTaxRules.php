<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxRules\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\System\Filament\SystemAdmin\Resources\TaxRules\TaxRuleResource;

final class ListTaxRules extends BaseListRecords
{
    protected static string $resource = TaxRuleResource::class;
}
