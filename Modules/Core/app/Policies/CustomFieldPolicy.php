<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\CustomField;
use Modules\People\Models\User;

final class CustomFieldPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('business_partners.view');
    }

    public function view(User $user, CustomField $customField): bool
    {
        return $user->can('business_partners.view') && $user->canAccessTenant($customField->company);
    }

    public function create(User $user): bool
    {
        return $user->can('business_partners.update');
    }

    public function update(User $user, CustomField $customField): bool
    {
        return $user->can('business_partners.update') && $user->canAccessTenant($customField->company);
    }

    public function delete(User $user, CustomField $customField): bool
    {
        return $user->can('business_partners.delete') && $user->canAccessTenant($customField->company);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('business_partners.delete');
    }
}
