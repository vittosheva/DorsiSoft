<?php

declare(strict_types=1);

namespace Modules\Workflow\Listeners;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\People\Models\User;
use Modules\Workflow\Events\ApprovalRejected;

final class NotifyDocumentCreatorOnApprovalRejected implements ShouldQueue
{
    public function handle(ApprovalRejected $event): void
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
            ->title(__('Document rejected'))
            ->body(__('Your document was rejected (flow: :flow, step: :step).', [
                'step' => $event->step,
                'flow' => $event->flowKey,
                'notes' => $event->record->notes ?? '—',
            ]))
            ->danger()
            ->sendToDatabase($creator);
    }
}
