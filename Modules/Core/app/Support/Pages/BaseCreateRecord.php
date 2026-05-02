<?php

declare(strict_types=1);

namespace Modules\Core\Support\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Js;
use Modules\Core\Support\Actions\BackAction;
use Modules\Core\Support\Actions\CancelAction;
use Modules\Core\Support\Actions\ClearFieldAction;
use Modules\Core\Support\Concerns\HasBeforeHeadingActions;

abstract class BaseCreateRecord extends CreateRecord
{
    use HasBeforeHeadingActions;

    protected function getBeforeHeadingActions(): array
    {
        $url = $this->getResourceUrl();

        return [
            BackAction::make()
                ->alpineClickHandler('Livewire.navigate('.Js::from($url).')'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ClearFieldAction::make()
                ->requiresConfirmation()
                ->showNotification(false),
        ];
    }

    protected function getCancelFormAction(): Action
    {
        $url = $this->getResourceUrl();

        return CancelAction::make('cancel')
            ->alpineClickHandler('Livewire.navigate('.Js::from($url).')');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __(':record m created successfully.', ['record' => self::getResource()::getTitleCaseModelLabel()]);
    }
}
