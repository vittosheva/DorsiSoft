<?php

declare(strict_types=1);

namespace Modules\Sri\Listeners;

use Illuminate\Support\Facades\Auth;
use Modules\Sales\Events\WithholdingIssued;
use Modules\Sri\Jobs\ProcessElectronicDocument;

final class TriggerElectronicWithholding
{
    public function handle(WithholdingIssued $event): void
    {
        ProcessElectronicDocument::dispatch(
            modelClass: $event->withholding::class,
            modelId: $event->withholding->id,
            triggeredBy: Auth::id(),
        )->onQueue('electronic-billing');
    }
}
