<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\DeliveryGuideResource;

final class ListDeliveryGuides extends BaseListRecords
{
    protected static string $resource = DeliveryGuideResource::class;
}
