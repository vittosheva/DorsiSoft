<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\BusinessPartners\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Actions\SeparatorAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\BusinessPartnerResource;

final class EditBusinessPartner extends BaseEditRecord
{
    protected static string $resource = BusinessPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            SeparatorAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)
            ->loadMissing([
                'customerDetail',
                'supplierDetail',
                'carrierDetail',
                'addresses',
                'bankAccounts',
                'carrierVehicles',
            ]);
    }
}
