<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\Sales\Enums\SalesOrderStatusEnum;
use Modules\Sales\Models\SalesOrder;

final class SalesOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('sales_orders.view');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sales_orders.create');
    }

    public function update(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.update') && $salesOrder->status === SalesOrderStatusEnum::Pending;
    }

    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('sales_orders.delete');
    }

    public function restore(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('sales_orders.restore');
    }

    public function replicate(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.create');
    }
}
