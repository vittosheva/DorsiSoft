<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Inventory\Models\Lot;
use Modules\People\Models\User;

final class LotPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('lots.view');
    }

    public function view(User $user, Lot $lot): bool
    {
        return $user->can('lots.view');
    }

    public function create(User $user): bool
    {
        return $user->can('lots.create');
    }

    public function update(User $user, Lot $lot): bool
    {
        return $user->can('lots.update');
    }

    public function delete(User $user, Lot $lot): bool
    {
        return $user->can('lots.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('lots.delete');
    }

    public function restore(User $user, Lot $lot): bool
    {
        return $user->can('lots.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('lots.restore');
    }
}
