<?php

declare(strict_types=1);

namespace Modules\Core\Support\Infolists\Entries;

use Filament\Infolists\Components\TextEntry;
use Modules\Core\Support\Codes\CodeFormatter;

final class CodeTextEntry extends TextEntry
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->placeholder('—')
            ->formatStateUsing(fn (?string $state): ?string => CodeFormatter::present($state));
    }

    public static function getDefaultName(): ?string
    {
        return 'code';
    }
}
