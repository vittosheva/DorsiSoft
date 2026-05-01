<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Accounting\Models\JournalEntry;
use Modules\People\Models\User;

final class JournalEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('journal_entries.view');
    }

    public function view(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.view');
    }

    public function create(User $user): bool
    {
        return $user->can('journal_entries.create');
    }

    public function update(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.update') && $entry->isDraft();
    }

    public function delete(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.delete') && $entry->isDraft();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('journal_entries.delete');
    }

    public function approve(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.approve') && $entry->isDraft();
    }

    public function void(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.void') && $entry->isApproved();
    }

    public function restore(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('journal_entries.restore');
    }
}
