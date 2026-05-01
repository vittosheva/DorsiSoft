<?php

declare(strict_types=1);

namespace Modules\Workflow\Policies;

use Illuminate\Database\Eloquent\Model;
use Modules\Workflow\Contracts\Approvable;
use Modules\Workflow\Models\ApprovalRecord;

final class ApprovalRecordPolicy
{
    /**
     * Determines if a user can approve/reject a specific step on a given approvable.
     */
    public function approve(Model $user, Model $approvable, string $flowKey, string $step): bool
    {
        if (! $approvable instanceof Approvable) {
            return false;
        }

        return $approvable->canUserApprove($user, $flowKey, $step);
    }

    /**
     * Determines if a user can reset their own approval record.
     */
    public function reset(Model $user, ApprovalRecord $record): bool
    {
        return $record->approver_id === $user->getKey();
    }
}
