<?php

declare(strict_types=1);

namespace Modules\Workflow\Listeners;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\People\Models\User;
use Modules\Workflow\Contracts\Approvable;
use Modules\Workflow\Events\ApprovalRequested;
use Modules\Workflow\Models\WorkflowApprovalSetting;

/**
 * Notifies all users that can act on the requested approval step.
 *
 * Role resolution order:
 *   1. WorkflowApprovalSetting.required_roles (tenant override, if set)
 *   2. ApprovalStep hardcoded roles (code-side definition)
 */
final class NotifyApproversOnApprovalRequested implements ShouldQueue
{
    public function handle(ApprovalRequested $event): void
    {
        $approvable = $event->approvable;

        if (! $approvable instanceof Approvable) {
            return;
        }

        $companyId = $approvable->company_id ?? null;

        if (! $companyId) {
            return;
        }

        $flow = $approvable->getApprovalFlow($event->flowKey);
        $step = $flow?->getStep($event->stepName);

        if ($step === null) {
            return;
        }

        // Check tenant override roles first
        $setting = WorkflowApprovalSetting::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('flow_key', $event->flowKey)
            ->first();

        $roles = ! empty($setting?->required_roles)
            ? $setting->required_roles
            : $step->getRoles();

        if (empty($roles)) {
            return;
        }

        $approvers = User::withoutGlobalScopes()
            ->select(['id', 'name'])
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $roles)->where('company_id', $companyId))
            ->get();

        foreach ($approvers as $approver) {
            Notification::make()
                ->title(__('Approval required'))
                ->body(__('A document requires your approval (flow: :flow, step: :step).', [
                    'flow' => $event->flowKey,
                    'step' => $event->stepName,
                ]))
                ->warning()
                ->sendToDatabase($approver);
        }
    }
}
