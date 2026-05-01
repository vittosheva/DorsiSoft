<?php

declare(strict_types=1);

namespace Modules\Workflow\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Enums\RoleEnum;
use Modules\People\Models\User;
use Modules\Workflow\Models\ApprovalFlow;

final class ApprovalFlowPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isManager($user);
    }

    public function view(User $user, ApprovalFlow $approvalFlow): bool
    {
        return $this->isManager($user);
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, ApprovalFlow $approvalFlow): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, ApprovalFlow $approvalFlow): bool
    {
        return $this->isManager($user);
    }

    /** Only system admins and company admins may manage approval flow definitions. */
    private function isManager(User $user): bool
    {
        return $user->hasAnyRole([RoleEnum::SYSTEM_ADMIN->value, RoleEnum::ADMIN->value]);
    }
}
