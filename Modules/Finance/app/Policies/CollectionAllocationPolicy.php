<?php

declare(strict_types=1);

namespace Modules\Finance\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Finance\Models\CollectionAllocation;
use Modules\People\Models\User;

final class CollectionAllocationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('collections.view');
    }

    public function view(User $user, CollectionAllocation $allocation): bool
    {
        return $user->can('collections.view');
    }

    public function create(User $user): bool
    {
        return $user->can('collections.update');
    }

    public function update(User $user, CollectionAllocation $allocation): bool
    {
        return $user->can('collections.update');
    }

    public function delete(User $user, CollectionAllocation $allocation): bool
    {
        return $user->can('collections.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('collections.update');
    }
}
