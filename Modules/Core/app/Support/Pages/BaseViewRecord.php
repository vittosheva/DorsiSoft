<?php

declare(strict_types=1);

namespace Modules\Core\Support\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Js;
use Modules\Core\Support\Actions\BackAction;
use Modules\Core\Support\Actions\CancelAction;
use Modules\Core\Support\Concerns\HasBeforeHeadingActions;

abstract class BaseViewRecord extends ViewRecord
{
    use HasBeforeHeadingActions;

    final public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return __('View record title :label', [
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
            EditAction::make(),
        ];
    }

    protected function getCancelFormAction(): Action
    {
        $url = $this->getResourceUrl();

        return CancelAction::make('cancel')
            ->alpineClickHandler('Livewire.navigate('.Js::from($url).')');
    }
}
