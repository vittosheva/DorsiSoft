<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\Company;
use Modules\People\Models\User;

final class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('companies.view');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can('companies.view') && $user->canAccessTenant($company);
    }

    public function create(User $user): bool
    {
        if ($user->can('companies.create')) {
            return true;
        }

        $originalTeamId = getPermissionsTeamId();

        try {
            foreach ($user->companies()->select('core_companies.id')->pluck('core_companies.id') as $companyId) {
                setPermissionsTeamId((int) $companyId);

                $tenantScopedUser = User::query()->select(['id', 'name', 'email'])->find($user->getKey());

                if (($tenantScopedUser instanceof User) && $tenantScopedUser->can('companies.create')) {
                    return true;
                }
            }

            return false;
        } finally {
            setPermissionsTeamId($originalTeamId);
        }
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can('companies.update') && $user->canAccessTenant($company);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can('companies.delete') && $user->canAccessTenant($company);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('companies.delete');
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->can('companies.restore') && $user->canAccessTenant($company);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('companies.restore');
    }
}
