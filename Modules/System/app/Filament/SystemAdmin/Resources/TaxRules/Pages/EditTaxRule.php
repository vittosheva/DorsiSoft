<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxRules\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\System\Filament\SystemAdmin\Resources\TaxRules\TaxRuleResource;

final class EditTaxRule extends BaseEditRecord
{
    protected static string $resource = TaxRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DeleteAction::make(),
        ];
    }
}
