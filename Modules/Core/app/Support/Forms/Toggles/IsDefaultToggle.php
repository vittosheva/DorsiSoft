<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\Toggles;

use Filament\Forms\Components\Toggle;

final class IsDefaultToggle extends Toggle
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->inline(false)
            ->default(false)
            ->columnSpan(3);
    }

    public static function getDefaultName(): ?string
    {
        return 'is_default';
    }
}
