<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\Establishment;
use Modules\People\Models\User;

final class EstablishmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('establishments.view');
    }

    public function view(User $user, Establishment $establishment): bool
    {
        return $user->can('establishments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('establishments.create');
    }

    public function update(User $user, Establishment $establishment): bool
    {
        return $user->can('establishments.update');
    }

    public function delete(User $user, Establishment $establishment): bool
    {
        return $user->can('establishments.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('establishments.delete');
    }

    public function restore(User $user, Establishment $establishment): bool
    {
        return $user->can('establishments.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('establishments.restore');
    }
}
