<?php

declare(strict_types=1);

namespace Modules\System\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\System\Models\DocumentSeries;

final class DocumentSeriesPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('document_types.view');
    }

    public function view(User $user, DocumentSeries $documentSeries): bool
    {
        return $user->can('document_types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('document_types.update');
    }

    public function update(User $user, DocumentSeries $documentSeries): bool
    {
        return $user->can('document_types.update');
    }

    public function delete(User $user, DocumentSeries $documentSeries): bool
    {
        return $user->can('document_types.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('document_types.delete');
    }
}
