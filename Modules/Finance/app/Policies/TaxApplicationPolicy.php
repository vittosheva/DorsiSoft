<?php

declare(strict_types=1);

namespace Modules\Finance\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Finance\Models\TaxApplication;
use Modules\People\Models\User;

final class TaxApplicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('tax_applications.view');
    }

    public function view(User $user, TaxApplication $taxApplication): bool
    {
        return $user->can('tax_applications.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TaxApplication $taxApplication): bool
    {
        return false;
    }

    public function delete(User $user, TaxApplication $taxApplication): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
