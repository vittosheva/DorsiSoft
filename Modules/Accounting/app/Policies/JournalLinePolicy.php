<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Accounting\Models\JournalLine;
use Modules\People\Models\User;

final class JournalLinePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('journal_entries.view');
    }

    public function view(User $user, JournalLine $line): bool
    {
        return $user->can('journal_entries.view');
    }

    public function create(User $user): bool
    {
        return $user->can('journal_entries.create');
    }

    public function update(User $user, JournalLine $line): bool
    {
        return $user->can('journal_entries.update') && $line->journalEntry->isDraft();
    }

    public function delete(User $user, JournalLine $line): bool
    {
        return $user->can('journal_entries.delete') && $line->journalEntry->isDraft();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('journal_entries.delete');
    }

    public function restore(User $user, JournalLine $line): bool
    {
        return $user->can('journal_entries.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('journal_entries.restore');
    }
}
