<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\People\Models\User;

final class FiscalPeriodPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('fiscal_periods.manage');
    }

    public function view(User $user, FiscalPeriod $period): bool
    {
        return $user->company_id === $period->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('fiscal_periods.manage');
    }

    public function update(User $user, FiscalPeriod $period): bool
    {
        return $user->company_id === $period->company_id && $period->isOpen();
    }

    public function delete(User $user, FiscalPeriod $period): bool
    {
        return $user->company_id === $period->company_id && $period->isOpen();
    }

    public function close(User $user, FiscalPeriod $period): bool
    {
        return $user->company_id === $period->company_id && $user->can('fiscal_periods.close') && $period->isOpen();
    }

    public function reopen(User $user, FiscalPeriod $period): bool
    {
        return $user->company_id === $period->company_id && $user->can('fiscal_periods.close') && $period->isClosed();
    }
}
