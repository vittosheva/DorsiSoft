<?php

declare(strict_types=1);

namespace Modules\People\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;

final class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $record): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $record): bool
    {
        return $user->can('users.update');
    }

    public function delete(User $user, User $record): bool
    {
        return $user->can('users.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('users.delete');
    }

    public function restore(User $user, User $record): bool
    {
        return $user->can('users.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('users.restore');
    }
}
