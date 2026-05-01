<?php

declare(strict_types=1);

namespace Modules\Sales\Livewire\Concerns;

use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

trait HasMinimumItemsValidation
{
    #[Locked]
    public int $minimumItemsCount = 0;

    #[Locked]
    public ?string $minimumItemsValidationMessage = null;

    protected function initializeMinimumItemsValidation(int $minimumItemsCount = 0, ?string $minimumItemsValidationMessage = null): void
    {
        $this->minimumItemsCount = $minimumItemsCount;
        $this->minimumItemsValidationMessage = $minimumItemsValidationMessage;
    }

    protected function dispatchDocumentItemsCountUpdated(): void
    {
        $this->dispatch('document-items-count-updated',
            count: count($this->pendingItems),
            total: (float) array_sum(array_column($this->pendingItems, 'total')),
        );
    }

    protected function validateMinimumItems(): void
    {
        if (count($this->pendingItems) >= $this->minimumItemsCount) {
            return;
        }

        throw ValidationException::withMessages([
            'pendingItems' => $this->minimumItemsValidationMessage
                ?? __('Add at least :count item(s).', ['count' => $this->minimumItemsCount]),
        ]);
    }
}
