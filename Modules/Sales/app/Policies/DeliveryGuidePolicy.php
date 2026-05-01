<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\Sales\Enums\DeliveryGuideStatusEnum;
use Modules\Sales\Models\DeliveryGuide;

final class DeliveryGuidePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('delivery_guides.view');
    }

    public function view(User $user, DeliveryGuide $deliveryGuide): bool
    {
        return $user->can('delivery_guides.view');
    }

    public function create(User $user): bool
    {
        return $user->can('delivery_guides.create');
    }

    public function update(User $user, DeliveryGuide $deliveryGuide): bool
    {
        if (! $user->can('delivery_guides.update')) {
            return false;
        }

        if ($deliveryGuide->isElectronicDocumentMutable()) {
            return true;
        }

        return $deliveryGuide->status === DeliveryGuideStatusEnum::Issued
            && ! $deliveryGuide->hasElectronicProcessingLock();
    }

    public function correctRejected(User $user, DeliveryGuide $deliveryGuide): bool
    {
        return $user->can('delivery_guides.update') && $deliveryGuide->canCorrectRejectedElectronicDocument();
    }

    public function retryElectronic(User $user, DeliveryGuide $deliveryGuide): bool
    {
        return $user->can('delivery_guides.update') && $deliveryGuide->canRetryElectronicProcessing();
    }

    public function delete(User $user, DeliveryGuide $deliveryGuide): bool
    {
        return $user->can('delivery_guides.delete') && $deliveryGuide->isElectronicDocumentMutable();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delivery_guides.delete');
    }

    public function restore(User $user, DeliveryGuide $deliveryGuide): bool
    {
        return $user->can('delivery_guides.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('delivery_guides.restore');
    }

    public function replicate(User $user, DeliveryGuide $deliveryGuide): bool
    {
        return $user->can('delivery_guides.create');
    }
}
