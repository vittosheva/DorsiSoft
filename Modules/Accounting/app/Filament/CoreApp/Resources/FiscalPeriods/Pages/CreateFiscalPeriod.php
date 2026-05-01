<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Pages;

use Filament\Schemas\Schema;
use Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\FiscalPeriodResource;
use Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Schemas\FiscalPeriodForm;
use Modules\Core\Support\Pages\BaseCreateRecord;

final class CreateFiscalPeriod extends BaseCreateRecord
{
    protected static string $resource = FiscalPeriodResource::class;

    public function form(Schema $schema): Schema
    {
        return FiscalPeriodForm::configure($schema);
    }
}
