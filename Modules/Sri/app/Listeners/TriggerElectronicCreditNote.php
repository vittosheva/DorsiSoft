<?php

declare(strict_types=1);

namespace Modules\Sri\Listeners;

use Illuminate\Support\Facades\Auth;
use Modules\Sales\Events\CreditNoteIssued;
use Modules\Sri\Jobs\ProcessElectronicDocument;

final class TriggerElectronicCreditNote
{
    public function handle(CreditNoteIssued $event): void
    {
        ProcessElectronicDocument::dispatch(
            modelClass: $event->creditNote::class,
            modelId: $event->creditNote->id,
            triggeredBy: Auth::id(),
        )->onQueue('electronic-billing');
    }
}
