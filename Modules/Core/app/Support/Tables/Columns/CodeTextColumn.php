<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Columns;

use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Modules\Core\Support\Codes\CodeFormatter;

final class CodeTextColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->placeholder('—')
            ->weight(FontWeight::SemiBold)
            ->fontFamily(FontFamily::Mono)
            // ->formatStateUsing(fn(?string $state): ?string => CodeFormatter::present($state))
            // ->alignment(Alignment::Center)
            // ->badge()
            ->sortable()
            ->searchable();
    }

    public static function getDefaultName(): ?string
    {
        return 'code';
    }
}
