<?php

declare(strict_types=1);

namespace Modules\System\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\System\Models\TaxRule;

final class TaxRulePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the tax rule.
     */
    public function view(User $user, TaxRule $taxRule): bool
    {
        return $user->can('tax_rules.view');
    }

    /**
     * Determine whether the user can view any tax rules.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('tax_rules.view');
    }

    /**
     * Determine whether the user can create tax rules.
     */
    public function create(User $user): bool
    {
        return $user->can('tax_rules.create');
    }

    /**
     * Determine whether the user can update the tax rule.
     */
    public function update(User $user, TaxRule $taxRule): bool
    {
        return $user->can('tax_rules.update');
    }

    /**
     * Determine whether the user can delete the tax rule.
     */
    public function delete(User $user, TaxRule $taxRule): bool
    {
        return $user->can('tax_rules.delete');
    }

    /**
     * Determine whether the user can delete any tax rules.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('tax_rules.delete');
    }
}
