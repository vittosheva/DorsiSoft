<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

trait SyncsDocumentItemsCount
{
    #[On('document-items-count-updated')]
    public function syncDocumentItemsCount(int $count, float $total = 0.0): void
    {
        $formattedTotal = number_format($total, 4, '.', '');

        data_set($this, 'data.document_items_count', $count);
        data_set($this, 'data.document_items_total', $formattedTotal);

        if (! $this->supportsSriPayments()) {
            if (is_array($this->data ?? []) && array_key_exists('sri_payments', $this->data)) {
                unset($this->data['sri_payments']);
            }

            if ($count >= 1) {
                $this->resetValidation('data.document_items_count');
            }

            return;
        }

        $payments = $this->data['sri_payments'] ?? null;

        if (is_array($payments) && count($payments) === 1) {
            $paymentKey = array_key_first($payments);

            if ($paymentKey !== null) {
                data_set($this, "data.sri_payments.{$paymentKey}.amount", number_format($total, 2, '.', ''));
            }
        }

        if ($count >= 1) {
            $this->resetValidation('data.document_items_count');
        }
    }

    protected function beforeCreate(): void
    {
        $this->validatePaymentsMatchTotal();
    }

    protected function beforeSave(): void
    {
        $this->validatePaymentsMatchTotal();
    }

    protected function supportsSriPayments(): bool
    {
        return true;
    }

    private function validatePaymentsMatchTotal(): void
    {
        if (! $this->supportsSriPayments()) {
            return;
        }

        if (! array_key_exists('sri_payments', $this->data)) {
            return;
        }

        $itemsTotal = round((float) ($this->data['document_items_total'] ?? 0), 2);
        $paymentsTotal = round(
            collect($this->data['sri_payments'] ?? [])->sum(fn (array $p) => (float) ($p['amount'] ?? 0)),
            2,
        );

        if ($paymentsTotal !== $itemsTotal) {
            throw ValidationException::withMessages([
                'data.sri_payments' => [__('The sum of payments (:payments) must equal the document total (:total).', [
                    'payments' => number_format($paymentsTotal, 2),
                    'total' => number_format($itemsTotal, 2),
                ])],
            ]);
        }
    }
}
