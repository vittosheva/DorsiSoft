<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Inventory\Models\InventoryBalance;
use Modules\People\Models\User;

final class InventoryBalancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('inventory_balances.view');
    }

    public function view(User $user, InventoryBalance $inventoryBalance): bool
    {
        return $user->can('inventory_balances.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory_balances.create');
    }

    public function update(User $user, InventoryBalance $inventoryBalance): bool
    {
        return $user->can('inventory_balances.update');
    }

    public function delete(User $user, InventoryBalance $inventoryBalance): bool
    {
        return $user->can('inventory_balances.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('inventory_balances.delete');
    }

    public function restore(User $user, InventoryBalance $inventoryBalance): bool
    {
        return $user->can('inventory_balances.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('inventory_balances.restore');
    }
}
