<?php

declare(strict_types=1);

namespace Modules\Core\Support\Pages;

use BalisMatz\FilamentPreventOutdatedRecordUpdate\Concerns\PreventsOutdatedRecordUpdate;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Js;
use Modules\Core\Support\Actions\BackAction;
use Modules\Core\Support\Actions\CancelAction;
use Modules\Core\Support\Concerns\HasBeforeHeadingActions;

abstract class BaseEditRecord extends EditRecord
{
    use HasBeforeHeadingActions;
    use PreventsOutdatedRecordUpdate;

    final public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return __('Edit record title :label', [
            'label' => $this->getRecordTitle(),
        ]);
    }

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
            CreateAction::make(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterFill(): void
    {
        $record = $this->getRecord();

        if (
            method_exists($record, 'isSnapshotStale')
            && method_exists($record, 'isEditable')
            && $record->isEditable()
            && $record->isSnapshotStale()
        ) {
            $notification = Notification::make()
                ->warning()
                ->title(__('Contact data outdated'))
                ->body(__('The contact updated their data after this document was created. Use "Update contact data" to refresh.'))
                ->persistent();

            $this->dispatch('notificationSent', notification: $notification->toArray());
        }
    }

    protected function getCancelFormAction(): Action
    {
        $url = $this->getResourceUrl();

        return CancelAction::make('cancel')
            ->alpineClickHandler('Livewire.navigate('.Js::from($url).')');
    }
}
