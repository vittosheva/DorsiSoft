<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\People\Models\User;
use Modules\Sales\Models\Invoice;

final class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.update') && $invoice->isElectronicDocumentMutable();
    }

    public function correctRejected(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.update') && $invoice->canCorrectRejectedElectronicDocument();
    }

    public function retryElectronic(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.update') && $invoice->canRetryElectronicProcessing();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.delete') && $invoice->isElectronicDocumentMutable();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('invoices.delete');
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('invoices.restore');
    }

    public function replicate(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.create');
    }
}
