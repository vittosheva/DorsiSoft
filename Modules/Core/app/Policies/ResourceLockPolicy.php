<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Blendbyte\FilamentResourceLock\Models\ResourceLock;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;

final class ResourceLockPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('settings.view');
    }

    public function view(User $user, ResourceLock $resourceLock): bool
    {
        return $user->can('settings.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ResourceLock $resourceLock): bool
    {
        return false;
    }

    public function delete(User $user, ResourceLock $resourceLock): bool
    {
        return $user->can('settings.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function restore(User $user, ResourceLock $resourceLock): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }
}
