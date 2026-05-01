<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Sri\Concerns\HasElectronicEvents;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Models\SriElectronicEvent;

final class ElectronicEventLogger
{
    /**
     * @param  Model&HasElectronicBilling&HasElectronicEvents  $document
     */
    public static function record(
        HasElectronicBilling $document,
        string $event,
        ?ElectronicStatusEnum $statusFrom = null,
        ?ElectronicStatusEnum $statusTo = null,
        array $payload = [],
        ?int $triggeredBy = null,
    ): SriElectronicEvent {
        /** @var Model&HasElectronicBilling $document */
        return SriElectronicEvent::create([
            'company_id' => $document->company_id,
            'documentable_type' => $document->getMorphClass(),
            'documentable_id' => $document->getKey(),
            'event' => $event,
            'status_from' => $statusFrom?->value,
            'status_to' => $statusTo?->value,
            'triggered_by' => $triggeredBy,
            'payload' => blank($payload) ? null : $payload,
        ]);
    }
}
