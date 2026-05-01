<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\Sales\Models\CreditNoteApplication;

final class CreditNoteApplicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('credit_notes.view');
    }

    public function view(User $user, CreditNoteApplication $application): bool
    {
        return $user->can('credit_notes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('credit_notes.create');
    }

    public function delete(User $user, CreditNoteApplication $application): bool
    {
        return $user->can('credit_notes.delete');
    }
}
