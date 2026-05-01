<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Inventory\Models\Category;
use Modules\People\Models\User;

final class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('categories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('categories.delete');
    }

    public function restore(User $user, Category $category): bool
    {
        return $user->can('categories.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('categories.restore');
    }
}
