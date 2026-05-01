<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Columns;

use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;

final class IsActiveColumn extends IconColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->boolean()
            ->alignment(Alignment::Center)
            ->tooltip(fn (?Model $record): string => $record?->getAttribute('is_active')
                ? __('Click to deactivate this record.')
                : __('Click to reactivate this record.'))
            ->action(function (HasTable $livewire, ?Model $record): void {
                if ($record === null) {
                    return;
                }

                $this->toggleAttribute($record);
            })
            ->sortable();
    }

    public static function getDefaultName(): ?string
    {
        return 'is_active';
    }

    protected function toggleAttribute(Model $record): void
    {
        $attribute = $this->getName();

        if ($attribute === null) {
            return;
        }

        $newValue = ! (bool) $record->getAttribute($attribute);

        if (! $newValue && $record->hasAttribute('is_default') && $record->getAttribute('is_default')) {
            Notification::make()
                ->warning()
                ->title(__('Default record protected'))
                ->body(__('Remove the default flag before deactivating this record.'))
                ->send();

            return;
        }

        $record->update([$attribute => $newValue]);

        Notification::make()
            ->success()
            ->title($newValue ? __('Record activated') : __('Record deactivated'))
            ->body($newValue
                ? __('This record is now active.')
                : __('This record is now inactive.'))
            ->send();
    }
}
