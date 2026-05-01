<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

final class SriSequentialTextColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Sequence Emission'))
            ->formatStateUsing(function ($state, $record): string {
                $establishmentCode = $record->establishment_code ?? '000';
                $emissionPointCode = $record->emission_point_code ?? '000';
                $sequentialNumber = $record->sequential_number ?? '000000000';

                return mb_ltrim("{$establishmentCode}-{$emissionPointCode}-{$sequentialNumber}");
            })
            ->placeholder('—');
    }

    public static function getDefaultName(): ?string
    {
        return 'sequential_number';
    }
}
