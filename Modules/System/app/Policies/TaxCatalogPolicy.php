<?php

declare(strict_types=1);

namespace Modules\System\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\System\Models\TaxCatalog;

final class TaxCatalogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('tax_catalogs.view');
    }

    public function view(User $user, TaxCatalog $taxCatalog): bool
    {
        return $user->can('tax_catalogs.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tax_catalogs.create');
    }

    public function update(User $user, TaxCatalog $taxCatalog): bool
    {
        return $user->can('tax_catalogs.update');
    }

    public function delete(User $user, TaxCatalog $taxCatalog): bool
    {
        return $user->can('tax_catalogs.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('tax_catalogs.delete');
    }
}
