<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Filament\Notifications\Notification;

final class SriValidationNotifier
{
    public function notifyInvalidRuc(): void
    {
        Notification::make()
            ->title(__('Enter a valid RUC to validate.'))
            ->warning()
            ->send();
    }

    public function notifyValidationFailure(): void
    {
        Notification::make()
            ->title(__('Unable to validate the RUC with the SRI.'))
            ->danger()
            ->send();
    }

    public function notifyValidated(): void
    {
        Notification::make()
            ->title(__('RUC validated successfully in the SRI.'))
            ->success()
            ->send();
    }

    public function notifyNoInformation(): void
    {
        Notification::make()
            ->title(__('No information found for this RUC in the SRI.'))
            ->warning()
            ->send();
    }
}
