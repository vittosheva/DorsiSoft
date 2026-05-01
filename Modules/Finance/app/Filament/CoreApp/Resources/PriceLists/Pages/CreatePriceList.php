<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\PriceLists\Pages;

use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\PriceListResource;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\Schemas\PriceListForm;

final class CreatePriceList extends BaseCreateRecord
{
    protected static string $resource = PriceListResource::class;

    public function form(Schema $schema): Schema
    {
        return PriceListForm::configure($schema);
    }
}
