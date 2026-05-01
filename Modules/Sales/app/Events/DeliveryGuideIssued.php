<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\DeliveryGuide;

final class DeliveryGuideIssued
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly DeliveryGuide $deliveryGuide,
    ) {}
}
