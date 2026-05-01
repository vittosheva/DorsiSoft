<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Concerns;

use Filament\Notifications\Notification;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicStatusEnum;

/**
 * Shows a persistent Filament danger notification when a document's
 * electronic_status is Error and a human-readable error is stored in metadata.
 *
 * Apply this trait to any ViewRecord page that uses SRI electronic billing actions.
 */
trait ShowsElectronicError
{
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->notifyElectronicErrorIfPresent();
    }

    private function notifyElectronicErrorIfPresent(): void
    {
        $record = $this->getRecord();

        if (! $record instanceof HasElectronicBilling) {
            return;
        }

        if ($record->getElectronicStatus() !== ElectronicStatusEnum::Error) {
            return;
        }

        $errorMessage = $record->metadata['error'] ?? null;

        if (blank($errorMessage)) {
            return;
        }

        Notification::make()
            ->title(__('Electronic processing error'))
            ->body(__($errorMessage))
            ->danger()
            ->persistent()
            ->send();
    }
}
