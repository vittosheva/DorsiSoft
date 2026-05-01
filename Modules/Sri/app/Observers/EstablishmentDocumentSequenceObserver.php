<?php

declare(strict_types=1);

namespace Modules\Sri\Observers;

use Modules\Core\Models\Establishment;
use Modules\Sri\Services\DocumentSequenceSyncService;

final class EstablishmentDocumentSequenceObserver
{
    public function created(Establishment $establishment): void
    {
        app(DocumentSequenceSyncService::class)->syncForEstablishment($establishment);
    }

    public function updated(Establishment $establishment): void
    {
        if (! $establishment->wasChanged(['is_active', 'code'])) {
            return;
        }

        app(DocumentSequenceSyncService::class)->syncForEstablishment($establishment);
    }

    public function restored(Establishment $establishment): void
    {
        app(DocumentSequenceSyncService::class)->syncForEstablishment($establishment);
    }
}
