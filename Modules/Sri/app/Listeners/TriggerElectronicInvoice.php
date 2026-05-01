<?php

declare(strict_types=1);

namespace Modules\Sri\Listeners;

use Illuminate\Support\Facades\Auth;
use Modules\Sales\Events\InvoiceIssued;
use Modules\Sri\Jobs\ProcessElectronicDocument;

final class TriggerElectronicInvoice
{
    public function handle(InvoiceIssued $event): void
    {
        ProcessElectronicDocument::dispatch(
            modelClass: $event->invoice::class,
            modelId: $event->invoice->id,
            triggeredBy: Auth::id(),
        )->onQueue('electronic-billing');
    }
}
