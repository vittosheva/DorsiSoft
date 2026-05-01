<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\Sales\Models\Withholding;

final class WithholdingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('withholdings.view');
    }

    public function view(User $user, Withholding $withholding): bool
    {
        return $user->can('withholdings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('withholdings.create');
    }

    public function update(User $user, Withholding $withholding): bool
    {
        return $user->can('withholdings.update') && $withholding->isElectronicDocumentMutable();
    }

    public function correctRejected(User $user, Withholding $withholding): bool
    {
        return $user->can('withholdings.update') && $withholding->canCorrectRejectedElectronicDocument();
    }

    public function retryElectronic(User $user, Withholding $withholding): bool
    {
        return $user->can('withholdings.update') && $withholding->canRetryElectronicProcessing();
    }

    public function delete(User $user, Withholding $withholding): bool
    {
        return $user->can('withholdings.delete') && $withholding->isElectronicDocumentMutable();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('withholdings.delete');
    }

    public function restore(User $user, Withholding $withholding): bool
    {
        return $user->can('withholdings.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('withholdings.restore');
    }

    public function replicate(User $user, Withholding $withholding): bool
    {
        return $user->can('withholdings.create');
    }
}
