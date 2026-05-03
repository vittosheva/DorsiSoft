<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Tables\Columns;

use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Modules\Sri\Enums\ElectronicStatusEnum;

final class ElectronicStatusColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('SRI Status'))
            ->badge()
            // ->color(fn(?ElectronicStatusEnum $state): string|array|null => $state?->getColor())
            // ->icon(fn (?ElectronicStatusEnum $state): ?string => $state?->getIcon())
            // ->formatStateUsing(fn(?ElectronicStatusEnum $state): string => $state?->getLabel() ?? '—')
            ->tooltip(fn (?Model $record): string => $record?->metadata['error'] ?? '')
            ->alignment(Alignment::Center)
            ->sortable()
            ->toggleable();
    }

    public static function getDefaultName(): ?string
    {
        return 'electronic_status';
    }
}
