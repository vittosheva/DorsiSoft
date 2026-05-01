<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\Sales\Models\Quotation;

final class QuotationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('quotations.view');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('quotations.create');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.update');
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('quotations.delete');
    }

    public function restore(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('quotations.restore');
    }

    public function replicate(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.create');
    }
}
