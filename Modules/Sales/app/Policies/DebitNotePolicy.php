<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\Sales\Models\DebitNote;

final class DebitNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('debit_notes.view');
    }

    public function view(User $user, DebitNote $debitNote): bool
    {
        return $user->can('debit_notes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('debit_notes.create');
    }

    public function update(User $user, DebitNote $debitNote): bool
    {
        return $user->can('debit_notes.update') && $debitNote->isElectronicDocumentMutable();
    }

    public function correctRejected(User $user, DebitNote $debitNote): bool
    {
        return $user->can('debit_notes.update') && $debitNote->canCorrectRejectedElectronicDocument();
    }

    public function retryElectronic(User $user, DebitNote $debitNote): bool
    {
        return $user->can('debit_notes.update') && $debitNote->canRetryElectronicProcessing();
    }

    public function delete(User $user, DebitNote $debitNote): bool
    {
        return $user->can('debit_notes.delete') && $debitNote->isElectronicDocumentMutable();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('debit_notes.delete');
    }

    public function restore(User $user, DebitNote $debitNote): bool
    {
        return $user->can('debit_notes.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('debit_notes.restore');
    }

    public function replicate(User $user, DebitNote $debitNote): bool
    {
        return $user->can('debit_notes.create');
    }
}
