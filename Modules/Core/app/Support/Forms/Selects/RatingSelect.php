<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\Selects;

use Filament\Forms\Components\Select;

final class RatingSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->options([
                'A' => 'A',
                'B' => 'B',
                'C' => 'C',
            ])
            ->default('A')
            ->native(false);
    }

    public static function getDefaultName(): ?string
    {
        return 'rating';
    }
}
