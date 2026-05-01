<?php

declare(strict_types=1);

namespace Modules\Finance\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Finance\Models\PriceList;
use Modules\People\Models\User;

final class PriceListPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('price_lists.view');
    }

    public function view(User $user, PriceList $priceList): bool
    {
        return $user->can('price_lists.view');
    }

    public function create(User $user): bool
    {
        return $user->can('price_lists.create');
    }

    public function update(User $user, PriceList $priceList): bool
    {
        return $user->can('price_lists.update');
    }

    public function delete(User $user, PriceList $priceList): bool
    {
        return $user->can('price_lists.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('price_lists.delete');
    }

    public function restore(User $user, PriceList $priceList): bool
    {
        return $user->can('price_lists.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('price_lists.restore');
    }
}
