<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\Sales\Models\TaxWithholdingRule;

final class TaxWithholdingRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('tax_withholding_rules.view');
    }

    public function view(User $user, TaxWithholdingRule $rule): bool
    {
        return $user->can('tax_withholding_rules.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tax_withholding_rules.create');
    }

    public function update(User $user, TaxWithholdingRule $rule): bool
    {
        return $user->can('tax_withholding_rules.update');
    }

    public function delete(User $user, TaxWithholdingRule $rule): bool
    {
        return $user->can('tax_withholding_rules.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('tax_withholding_rules.delete');
    }

    public function restore(User $user, TaxWithholdingRule $rule): bool
    {
        return $user->can('tax_withholding_rules.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('tax_withholding_rules.restore');
    }
}
