<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Inventory\Models\SerialNumber;
use Modules\People\Models\User;

final class SerialNumberPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('serial_numbers.view');
    }

    public function view(User $user, SerialNumber $serialNumber): bool
    {
        return $user->can('serial_numbers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('serial_numbers.create');
    }

    public function update(User $user, SerialNumber $serialNumber): bool
    {
        return $user->can('serial_numbers.update');
    }

    public function delete(User $user, SerialNumber $serialNumber): bool
    {
        return $user->can('serial_numbers.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('serial_numbers.delete');
    }

    public function restore(User $user, SerialNumber $serialNumber): bool
    {
        return $user->can('serial_numbers.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('serial_numbers.restore');
    }
}
