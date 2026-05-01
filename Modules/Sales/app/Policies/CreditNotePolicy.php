<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\Sales\Models\CreditNote;

final class CreditNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('credit_notes.view');
    }

    public function view(User $user, CreditNote $creditNote): bool
    {
        return $user->can('credit_notes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('credit_notes.create');
    }

    public function update(User $user, CreditNote $creditNote): bool
    {
        return $user->can('credit_notes.update') && $creditNote->isElectronicDocumentMutable();
    }

    public function correctRejected(User $user, CreditNote $creditNote): bool
    {
        return $user->can('credit_notes.update') && $creditNote->canCorrectRejectedElectronicDocument();
    }

    public function retryElectronic(User $user, CreditNote $creditNote): bool
    {
        return $user->can('credit_notes.update') && $creditNote->canRetryElectronicProcessing();
    }

    public function delete(User $user, CreditNote $creditNote): bool
    {
        return $user->can('credit_notes.delete') && $creditNote->isElectronicDocumentMutable();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('credit_notes.delete');
    }

    public function restore(User $user, CreditNote $creditNote): bool
    {
        return $user->can('credit_notes.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('credit_notes.restore');
    }

    public function replicate(User $user, CreditNote $creditNote): bool
    {
        return $user->can('credit_notes.create');
    }
}
