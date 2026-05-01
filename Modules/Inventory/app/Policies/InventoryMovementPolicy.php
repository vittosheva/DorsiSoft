<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Inventory\Models\InventoryMovement;
use Modules\People\Models\User;

final class InventoryMovementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('inventory_movements.view');
    }

    public function view(User $user, InventoryMovement $movement): bool
    {
        return $user->can('inventory_movements.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory_movements.create');
    }

    public function update(User $user, InventoryMovement $movement): bool
    {
        return $user->can('inventory_movements.update');
    }

    public function delete(User $user, InventoryMovement $movement): bool
    {
        return $user->can('inventory_movements.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('inventory_movements.delete');
    }

    public function restore(User $user, InventoryMovement $movement): bool
    {
        return $user->can('inventory_movements.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('inventory_movements.restore');
    }

    public function void(User $user, InventoryMovement $movement): bool
    {
        return $user->can('inventory_movements.void');
    }
}
