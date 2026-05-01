<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\BusinessPartners\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\BusinessPartnerResource;

final class CreateBusinessPartner extends BaseCreateRecord
{
    protected static string $resource = BusinessPartnerResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return __(':record f created successfully.', ['record' => self::getResource()::getTitleCaseModelLabel()]);
    }
}
