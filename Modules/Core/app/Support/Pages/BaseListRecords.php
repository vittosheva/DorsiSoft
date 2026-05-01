<?php

declare(strict_types=1);

namespace Modules\Core\Support\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

abstract class BaseListRecords extends ListRecords
{
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
