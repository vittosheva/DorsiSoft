<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Inventory\Models\Brand;
use Modules\People\Models\User;

final class BrandPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('brands.view');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->can('brands.view');
    }

    public function create(User $user): bool
    {
        return $user->can('brands.create');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can('brands.update');
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->can('brands.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('brands.delete');
    }

    public function restore(User $user, Brand $brand): bool
    {
        return $user->can('brands.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('brands.restore');
    }
}
