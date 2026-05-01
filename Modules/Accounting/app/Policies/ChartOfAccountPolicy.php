<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\People\Models\User;

final class ChartOfAccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('chart_of_accounts.view');
    }

    public function view(User $user, ChartOfAccount $account): bool
    {
        return $user->can('chart_of_accounts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('chart_of_accounts.create');
    }

    public function update(User $user, ChartOfAccount $account): bool
    {
        return $user->can('chart_of_accounts.update');
    }

    public function delete(User $user, ChartOfAccount $account): bool
    {
        if (! $user->can('chart_of_accounts.delete')) {
            return false;
        }

        return ! $account->journalLines()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('chart_of_accounts.delete');
    }

    public function restore(User $user, ChartOfAccount $account): bool
    {
        return $user->can('chart_of_accounts.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('chart_of_accounts.restore');
    }
}
