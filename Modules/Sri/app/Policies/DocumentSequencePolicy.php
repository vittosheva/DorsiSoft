<?php

declare(strict_types=1);

namespace Modules\Sri\Policies;

use Modules\People\Models\User;
use Modules\Sri\Models\DocumentSequence;

final class DocumentSequencePolicy
{
    /**
     * Determine whether the user can view any document sequences.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the document sequence.
     */
    public function view(User $user, DocumentSequence $documentSequence): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create document sequences.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the document sequence.
     */
    public function update(User $user, DocumentSequence $documentSequence): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the document sequence.
     */
    public function delete(User $user, DocumentSequence $documentSequence): bool
    {
        return true;
    }
}
