<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\DeliveryGuideResource;

final class CreateDeliveryGuide extends BaseCreateRecord
{
    protected static string $resource = DeliveryGuideResource::class;
}
