<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Inventory\Models\Unit;
use Modules\People\Models\User;

final class UnitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('units.view');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->can('units.view');
    }

    public function create(User $user): bool
    {
        return $user->can('units.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('units.update');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('units.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('units.delete');
    }

    public function restore(User $user, Unit $unit): bool
    {
        return $user->can('units.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('units.restore');
    }
}
