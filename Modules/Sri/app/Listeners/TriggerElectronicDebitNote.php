<?php

declare(strict_types=1);

namespace Modules\Sri\Listeners;

use Illuminate\Support\Facades\Auth;
use Modules\Sales\Events\DebitNoteIssued;
use Modules\Sri\Jobs\ProcessElectronicDocument;

final class TriggerElectronicDebitNote
{
    public function handle(DebitNoteIssued $event): void
    {
        ProcessElectronicDocument::dispatch(
            modelClass: $event->debitNote::class,
            modelId: $event->debitNote->id,
            triggeredBy: Auth::id(),
        )->onQueue('electronic-billing');
    }
}
