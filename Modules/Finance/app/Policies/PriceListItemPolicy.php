<?php

declare(strict_types=1);

namespace Modules\Finance\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Finance\Models\PriceListItem;
use Modules\People\Models\User;

final class PriceListItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('price_list_items.view');
    }

    public function view(User $user, PriceListItem $priceListItem): bool
    {
        return $user->can('price_list_items.view');
    }

    public function create(User $user): bool
    {
        return $user->can('price_list_items.create');
    }

    public function update(User $user, PriceListItem $priceListItem): bool
    {
        return $user->can('price_list_items.update');
    }

    public function delete(User $user, PriceListItem $priceListItem): bool
    {
        return $user->can('price_list_items.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('price_list_items.delete');
    }
}
