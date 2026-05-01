<?php

declare(strict_types=1);

namespace Modules\People\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\People\Services\FinalConsumerRegistry;

final class BusinessPartnerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('business_partners.view');
    }

    public function view(User $user, BusinessPartner $businessPartner): bool
    {
        if ($user->business_partner_id) {
            return $user->business_partner_id === $businessPartner->getKey();
        }

        return $user->can('business_partners.view') && $user->canAccessTenant($businessPartner->company);
    }

    public function create(User $user): bool
    {
        return $user->can('business_partners.create');
    }

    public function update(User $user, BusinessPartner $businessPartner): bool
    {
        if (app(FinalConsumerRegistry::class)->isFinalConsumer($businessPartner)) {
            return false;
        }

        if ($user->business_partner_id) {
            return $user->business_partner_id === $businessPartner->getKey();
        }

        return $user->can('business_partners.update') && $user->canAccessTenant($businessPartner->company);
    }

    public function delete(User $user, BusinessPartner $businessPartner): bool
    {
        return $user->can('business_partners.delete') && $user->canAccessTenant($businessPartner->company);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('business_partners.delete');
    }

    public function restore(User $user, BusinessPartner $businessPartner): bool
    {
        return $user->can('business_partners.restore') && $user->canAccessTenant($businessPartner->company);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('business_partners.restore');
    }
}
