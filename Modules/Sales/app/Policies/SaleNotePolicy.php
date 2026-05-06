<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Modules\People\Models\User;
use Modules\Sales\Models\SaleNote;

final class SaleNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sale_notes.view');
    }

    public function view(User $user, SaleNote $saleNote): bool
    {
        return $user->can('sale_notes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sale_notes.create');
    }

    public function update(User $user, SaleNote $saleNote): bool
    {
        return $user->can('sale_notes.update') && $saleNote->status->isEditable();
    }

    public function delete(User $user, SaleNote $saleNote): bool
    {
        return $user->can('sale_notes.delete') && $saleNote->status->isEditable();
    }

    public function forceDelete(User $user, SaleNote $saleNote): bool
    {
        return $user->can('sale_notes.delete');
    }

    public function restore(User $user, SaleNote $saleNote): bool
    {
        return $user->can('sale_notes.delete');
    }
}
