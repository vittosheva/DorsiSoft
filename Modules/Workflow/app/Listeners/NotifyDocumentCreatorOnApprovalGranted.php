<?php

declare(strict_types=1);

namespace Modules\Workflow\Listeners;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\People\Models\User;
use Modules\Workflow\Events\ApprovalGranted;

final class NotifyDocumentCreatorOnApprovalGranted implements ShouldQueue
{
    public function handle(ApprovalGranted $event): void
    {
        $approvable = $event->record->approvable;

        if ($approvable === null || ! isset($approvable->created_by)) {
            return;
        }

        /** @var User|null $creator */
        $creator = User::withoutGlobalScopes()->find($approvable->created_by);

        if ($creator === null) {
            return;
        }

        Notification::make()
            ->title(__('Approved document'))
            ->body(__('Your document was approved (flow: :flow, step: :step).', [
                'step' => $event->step,
                'flow' => $event->flowKey,
            ]))
            ->success()
            ->sendToDatabase($creator);
    }
}
