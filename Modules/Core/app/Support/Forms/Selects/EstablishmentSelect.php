<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\Selects;

use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class EstablishmentSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->relationship(
                'establishment',
                'name',
                fn (Builder $query) => $query
                    ->select(['id', 'code', 'name'])
                    ->when(
                        filled(Filament::getTenant()?->getKey()),
                        fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant(), 'company'),
                    )
                    ->orderBy('code', 'asc')
                    ->limit(config('dorsi.filament.select_filter_options_limit', 50)),
            )
            ->getOptionLabelFromRecordUsing(fn (Model $record): string => mb_trim("{$record->code} - {$record->name}", ' -'))
            ->afterStateUpdated(function (Component $livewire, Field $component): void {
                $livewire->validateOnly($component->getStatePath());
            })
            ->partiallyRenderAfterStateUpdated()
            ->searchable()
            ->preload()
            ->required()
            ->default(fn (): ?int => Auth::user()->establishment_id ?? null)
            ->dehydrated(true)
            ->disabled(fn (): bool => Auth::user()->establishment_id !== null)
            ->selectablePlaceholder(fn (): bool => Auth::user()->establishment_id === null)
            ->live();
    }

    public static function getDefaultName(): ?string
    {
        return 'establishment_id';
    }
}
