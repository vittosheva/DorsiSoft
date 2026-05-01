<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\FiscalPeriodResource;
use Modules\Core\Support\Pages\BaseEditRecord;

final class EditFiscalPeriod extends BaseEditRecord
{
    protected static string $resource = FiscalPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DeleteAction::make(),
        ];
    }
}
