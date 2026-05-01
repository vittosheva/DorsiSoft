<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Inventory\Models\InventoryDocumentType;
use Modules\People\Models\User;

final class InventoryDocumentTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('inventory_document_types.view');
    }

    public function view(User $user, InventoryDocumentType $inventoryDocumentType): bool
    {
        return $user->can('inventory_document_types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory_document_types.create');
    }

    public function update(User $user, InventoryDocumentType $inventoryDocumentType): bool
    {
        return $user->can('inventory_document_types.update');
    }

    public function delete(User $user, InventoryDocumentType $inventoryDocumentType): bool
    {
        return $user->can('inventory_document_types.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('inventory_document_types.delete');
    }

    public function restore(User $user, InventoryDocumentType $inventoryDocumentType): bool
    {
        return $user->can('inventory_document_types.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('inventory_document_types.restore');
    }
}
