<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\Sales\Models\PurchaseSettlement;

final class PurchaseSettlementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        $tenant = filament()->getTenant();

        if ($tenant->is_accounting_required || ($tenant->is_special_taxpayer && $tenant->special_taxpayer_resolution !== null)) {
            if ($user->can('purchase_settlements.view')) {
                return true;
            }
        }

        return false;
    }

    public function view(User $user, PurchaseSettlement $purchaseSettlement): bool
    {
        return $user->can('purchase_settlements.view');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase_settlements.create');
    }

    public function update(User $user, PurchaseSettlement $purchaseSettlement): bool
    {
        return $user->can('purchase_settlements.update') && $purchaseSettlement->isElectronicDocumentMutable();
    }

    public function correctRejected(User $user, PurchaseSettlement $purchaseSettlement): bool
    {
        return $user->can('purchase_settlements.update') && $purchaseSettlement->canCorrectRejectedElectronicDocument();
    }

    public function retryElectronic(User $user, PurchaseSettlement $purchaseSettlement): bool
    {
        return $user->can('purchase_settlements.update') && $purchaseSettlement->canRetryElectronicProcessing();
    }

    public function delete(User $user, PurchaseSettlement $purchaseSettlement): bool
    {
        return $user->can('purchase_settlements.delete') && $purchaseSettlement->isElectronicDocumentMutable();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('purchase_settlements.delete');
    }

    public function replicate(User $user, PurchaseSettlement $purchaseSettlement): bool
    {
        return false;
    }

    public function restore(User $user, PurchaseSettlement $purchaseSettlement): bool
    {
        return $user->can('purchase_settlements.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('purchase_settlements.restore');
    }
}
