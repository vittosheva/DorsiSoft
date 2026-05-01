<?php

declare(strict_types=1);

namespace Modules\System\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\System\Models\TaxWithholdingRate;

final class TaxWithholdingRatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('tax_definitions.view');
    }

    public function view(User $user, TaxWithholdingRate $taxWithholdingRate): bool
    {
        return $user->can('tax_definitions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tax_definitions.create');
    }

    public function update(User $user, TaxWithholdingRate $taxWithholdingRate): bool
    {
        return $user->can('tax_definitions.update');
    }

    public function delete(User $user, TaxWithholdingRate $taxWithholdingRate): bool
    {
        return $user->can('tax_definitions.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('tax_definitions.delete');
    }

    public function restore(User $user, TaxWithholdingRate $taxWithholdingRate): bool
    {
        return $user->can('tax_definitions.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('tax_definitions.restore');
    }
}
