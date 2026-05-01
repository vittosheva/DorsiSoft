<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Blendbyte\FilamentResourceLock\Models\ResourceLockAudit;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;

final class ResourceLockAuditPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('settings.view');
    }

    public function view(User $user, ResourceLockAudit $resourceLockAudit): bool
    {
        return $user->can('settings.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ResourceLockAudit $resourceLockAudit): bool
    {
        return false;
    }

    public function delete(User $user, ResourceLockAudit $resourceLockAudit): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, ResourceLockAudit $resourceLockAudit): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }
}
