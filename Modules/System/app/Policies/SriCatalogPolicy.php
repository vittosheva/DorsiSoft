<?php

declare(strict_types=1);

namespace Modules\System\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\System\Models\SriCatalog;

final class SriCatalogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('sri_catalogs.view');
    }

    public function view(User $user, SriCatalog $sriCatalog): bool
    {
        return $user->can('sri_catalogs.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sri_catalogs.create');
    }

    public function update(User $user, SriCatalog $sriCatalog): bool
    {
        return $user->can('sri_catalogs.update');
    }

    public function delete(User $user, SriCatalog $sriCatalog): bool
    {
        return $user->can('sri_catalogs.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('sri_catalogs.delete');
    }
}
