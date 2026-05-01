<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Inventory\Models\Warehouse;
use Modules\People\Models\User;

final class WarehousePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('warehouses.view');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('warehouses.create');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.update');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('warehouses.delete');
    }

    public function restore(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('warehouses.restore');
    }
}
