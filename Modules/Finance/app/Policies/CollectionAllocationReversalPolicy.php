<?php

declare(strict_types=1);

namespace Modules\Finance\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Finance\Models\CollectionAllocationReversal;
use Modules\People\Models\User;

final class CollectionAllocationReversalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('collections.view');
    }

    public function view(User $user, CollectionAllocationReversal $reversal): bool
    {
        return $user->can('collections.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CollectionAllocationReversal $reversal): bool
    {
        return false;
    }

    public function delete(User $user, CollectionAllocationReversal $reversal): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
