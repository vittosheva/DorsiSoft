<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Tables\Columns;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;

final class CommercialStatusColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Document Status'))
            ->badge()
            ->sortable()
            ->getStateUsing(fn ($record) => method_exists($record, 'getDisplayCommercialStatus')
                ? $record->getDisplayCommercialStatus()
                : ($record->status ?? null))
            ->formatStateUsing(function ($state): string {
                if ($state instanceof HasLabel) {
                    return $state->getLabel();
                }

                if ($state instanceof BackedEnum) {
                    return str((string) $state->value)->replace('_', ' ')->headline()->toString();
                }

                if (is_string($state) && $state !== '') {
                    return str($state)->replace('_', ' ')->headline()->toString();
                }

                return '—';
            })
            ->color(function ($state): string|array|null {
                if ($state instanceof HasColor) {
                    return $state->getColor();
                }

                return match ((string) ($state instanceof BackedEnum ? $state->value : $state)) {
                    'issued' => 'success',
                    'paid', 'fully_applied' => 'info',
                    'voided' => 'danger',
                    default => 'gray',
                };
            })
            ->alignment(Alignment::Center);
    }

    public static function getDefaultName(): ?string
    {
        return 'status';
    }
}
