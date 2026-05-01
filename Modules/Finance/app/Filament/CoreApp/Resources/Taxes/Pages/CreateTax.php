<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Taxes\Pages;

use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Finance\Filament\CoreApp\Resources\Taxes\Schemas\TaxForm;
use Modules\Finance\Filament\CoreApp\Resources\Taxes\TaxResource;

final class CreateTax extends BaseCreateRecord
{
    protected static string $resource = TaxResource::class;

    public function form(Schema $schema): Schema
    {
        return TaxForm::configure($schema);
    }
}
