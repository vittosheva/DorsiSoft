<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Livewire\Attributes\On;

/**
 * Dispatches the Livewire items-persist event after a document is created or saved.
 *
 * Consuming Filament page classes must implement getItemsPersistEvent() returning
 * the event name that the embedded Livewire items component listens on.
 */
trait DispatchesItemsPersistEvent
{
    abstract protected function getItemsPersistEvent(): string;

    protected function afterCreate(): void
    {
        $this->dispatch($this->getItemsPersistEvent(), documentId: $this->record->getKey());
    }

    protected function afterSave(): void
    {
        $this->dispatch($this->getItemsPersistEvent(), documentId: $this->record->getKey());
    }
}
