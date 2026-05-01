<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\CustomFieldValue;
use Modules\People\Models\User;

final class CustomFieldValuePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('business_partners.view');
    }

    public function view(User $user, CustomFieldValue $customFieldValue): bool
    {
        return $this->canAccessValue($user, $customFieldValue, 'business_partners.view');
    }

    public function create(User $user): bool
    {
        return $user->can('business_partners.update');
    }

    public function update(User $user, CustomFieldValue $customFieldValue): bool
    {
        return $this->canAccessValue($user, $customFieldValue, 'business_partners.update');
    }

    public function delete(User $user, CustomFieldValue $customFieldValue): bool
    {
        return $this->canAccessValue($user, $customFieldValue, 'business_partners.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('business_partners.delete');
    }

    private function canAccessValue(User $user, CustomFieldValue $customFieldValue, string $permission): bool
    {
        if (! $user->can($permission)) {
            return false;
        }

        $customField = $customFieldValue->customField;
        $businessPartner = $customFieldValue->businessPartner;

        if (! $customField || ! $businessPartner) {
            return false;
        }

        if ((int) $customField->company_id !== (int) $businessPartner->company_id) {
            return false;
        }

        if (! $user->canAccessTenant($businessPartner->company)) {
            return false;
        }

        if (blank($customField->partner_role_id)) {
            return true;
        }

        return $businessPartner
            ->roles()
            ->whereKey($customField->partner_role_id)
            ->exists();
    }
}
