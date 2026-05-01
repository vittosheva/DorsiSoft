<?php

declare(strict_types=1);

namespace Modules\Finance\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Finance\Models\Tax;
use Modules\People\Models\User;

final class TaxPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('taxes.view');
    }

    public function view(User $user, Tax $tax): bool
    {
        return $user->can('taxes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('taxes.create');
    }

    public function update(User $user, Tax $tax): bool
    {
        return $user->can('taxes.update');
    }

    public function delete(User $user, Tax $tax): bool
    {
        return $user->can('taxes.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('taxes.delete');
    }

    public function restore(User $user, Tax $tax): bool
    {
        return $user->can('taxes.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('taxes.restore');
    }
}
