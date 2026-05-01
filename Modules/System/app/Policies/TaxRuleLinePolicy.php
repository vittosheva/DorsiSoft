<?php

declare(strict_types=1);

namespace Modules\System\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\System\Models\TaxRuleLine;

final class TaxRuleLinePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('tax_rules.view');
    }

    public function view(User $user, TaxRuleLine $taxRuleLine): bool
    {
        return $user->can('tax_rules.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tax_rules.create');
    }

    public function update(User $user, TaxRuleLine $taxRuleLine): bool
    {
        return $user->can('tax_rules.update');
    }

    public function delete(User $user, TaxRuleLine $taxRuleLine): bool
    {
        return $user->can('tax_rules.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('tax_rules.delete');
    }

    public function reorder(User $user): bool
    {
        return $user->can('tax_rules.update');
    }

    public function reorderAny(User $user): bool
    {
        return $user->can('tax_rules.update');
    }

    public function restore(User $user, TaxRuleLine $taxRuleLine): bool
    {
        return $user->can('tax_rules.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('tax_rules.restore');
    }
}
