<?php

declare(strict_types=1);

namespace Modules\System\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\System\Models\DocumentType;

final class DocumentTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('document_types.view');
    }

    public function view(User $user, DocumentType $documentType): bool
    {
        return $user->can('document_types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('document_types.create');
    }

    public function update(User $user, DocumentType $documentType): bool
    {
        return $user->can('document_types.update');
    }

    public function delete(User $user, DocumentType $documentType): bool
    {
        if (! $user->can('document_types.delete')) {
            return false;
        }

        return ! $documentType->series()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('document_types.delete');
    }

    public function restore(User $user, DocumentType $documentType): bool
    {
        return $user->can('document_types.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('document_types.restore');
    }
}
