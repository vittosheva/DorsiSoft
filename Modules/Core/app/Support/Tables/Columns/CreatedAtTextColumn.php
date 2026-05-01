<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

final class CreatedAtTextColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Created at'))
            ->placeholder('—')
            ->dateTime('d/m/Y H:i')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function getDefaultName(): ?string
    {
        return 'created_at';
    }
}
