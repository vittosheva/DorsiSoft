<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxRules\Pages;

use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\System\Filament\SystemAdmin\Resources\TaxRules\Schemas\TaxRuleForm;
use Modules\System\Filament\SystemAdmin\Resources\TaxRules\TaxRuleResource;

final class CreateTaxRule extends BaseCreateRecord
{
    protected static string $resource = TaxRuleResource::class;

    public function form(Schema $schema): Schema
    {
        return TaxRuleForm::configure($schema);
    }
}
