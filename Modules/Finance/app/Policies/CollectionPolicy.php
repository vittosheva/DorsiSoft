<?php

declare(strict_types=1);

namespace Modules\Finance\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Finance\Models\Collection;
use Modules\People\Models\User;

final class CollectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('collections.view');
    }

    public function view(User $user, Collection $collection): bool
    {
        return $user->can('collections.view');
    }

    public function create(User $user): bool
    {
        return $user->can('collections.create');
    }

    public function update(User $user, Collection $collection): bool
    {
        return $user->can('collections.update') && ! $collection->isVoided();
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $user->can('collections.delete') && ! $collection->isVoided();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('collections.delete');
    }

    public function restore(User $user, Collection $collection): bool
    {
        return $user->can('collections.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('collections.restore');
    }
}
