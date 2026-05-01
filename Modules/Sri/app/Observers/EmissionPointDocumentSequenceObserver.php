<?php

declare(strict_types=1);

namespace Modules\Sri\Observers;

use Modules\Core\Models\EmissionPoint;
use Modules\Sri\Services\DocumentSequenceSyncService;

final class EmissionPointDocumentSequenceObserver
{
    public function created(EmissionPoint $emissionPoint): void
    {
        app(DocumentSequenceSyncService::class)->syncForEmissionPoint($emissionPoint);
    }

    public function updated(EmissionPoint $emissionPoint): void
    {
        if (! $emissionPoint->wasChanged(['is_active', 'code', 'establishment_id'])) {
            return;
        }

        app(DocumentSequenceSyncService::class)->syncForEmissionPoint($emissionPoint);
    }

    public function restored(EmissionPoint $emissionPoint): void
    {
        app(DocumentSequenceSyncService::class)->syncForEmissionPoint($emissionPoint);
    }
}
