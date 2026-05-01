<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\EmissionPoint;
use Modules\People\Models\User;

final class EmissionPointPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('emission_points.view');
    }

    public function view(User $user, EmissionPoint $emissionPoint): bool
    {
        return $user->can('emission_points.view');
    }

    public function create(User $user): bool
    {
        return $user->can('emission_points.create');
    }

    public function update(User $user, EmissionPoint $emissionPoint): bool
    {
        return $user->can('emission_points.update');
    }

    public function delete(User $user, EmissionPoint $emissionPoint): bool
    {
        return $user->can('emission_points.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('emission_points.delete');
    }

    public function restore(User $user, EmissionPoint $emissionPoint): bool
    {
        return $user->can('emission_points.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('emission_points.restore');
    }
}
