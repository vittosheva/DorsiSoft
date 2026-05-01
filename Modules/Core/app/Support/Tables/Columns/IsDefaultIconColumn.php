<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Columns;

use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;

final class IsDefaultIconColumn extends IconColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->boolean()
            ->alignment(Alignment::Center)
            ->tooltip(fn (?Model $record): string => $record?->getAttribute('is_default')
                ? __('Click to remove the default flag from this record.')
                : __('Click to mark this record as the only default for its company.'))
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
        return 'is_default';
    }

    protected function toggleAttribute(Model $record): void
    {
        $attribute = $this->getName();

        if ($attribute === null) {
            return;
        }

        $newValue = ! (bool) $record->getAttribute($attribute);
        $activateRecord = $record->getAttribute('is_active');

        if (! $activateRecord) {
            Notification::make()
                ->info()
                ->title(__('Cannot mark an inactive record as default'))
                ->send();

            return;
        }

        if ($newValue && $activateRecord) {
            $this->resetDefaults($record, $attribute);
        }

        $data = [$attribute => $newValue];

        if ($activateRecord) {
            $data['is_active'] = true;
        }

        $record->update($data);

        Notification::make()
            ->success()
            ->title($newValue ? __('Default record updated') : __('Default flag cleared'))
            ->body($newValue
                ? ($activateRecord
                    ? __('This record is now the only default for its company and was reactivated to do so.')
                    : __('This record is now the only default for its company.'))
                : __('This record is no longer marked as default.'))
            ->send();
    }

    protected function resetDefaults(Model $record, string $attribute): void
    {
        $companyId = $record->getAttribute('company_id');

        $query = $record::query()
            ->where($attribute, true)
            ->whereKeyNot($record->getKey());

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $query->update([$attribute => false]);
    }
}
