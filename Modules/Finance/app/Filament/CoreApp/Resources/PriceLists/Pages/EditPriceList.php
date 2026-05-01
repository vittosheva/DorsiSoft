<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\PriceLists\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\PriceListResource;

final class EditPriceList extends BaseEditRecord
{
    protected static string $resource = PriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DeleteAction::make(),
        ];
    }
}
