<?php

declare(strict_types=1);

namespace Modules\Sri\Listeners;

use Illuminate\Support\Facades\Auth;
use Modules\Sales\Events\DeliveryGuideIssued;
use Modules\Sri\Jobs\ProcessElectronicDocument;

final class TriggerElectronicDeliveryGuide
{
    public function handle(DeliveryGuideIssued $event): void
    {
        ProcessElectronicDocument::dispatch(
            modelClass: $event->deliveryGuide::class,
            modelId: $event->deliveryGuide->id,
            triggeredBy: Auth::id(),
        )->onQueue('electronic-billing');
    }
}
