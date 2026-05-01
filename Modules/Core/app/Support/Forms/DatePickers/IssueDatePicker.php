<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\DatePickers;

use Filament\Forms\Components\DatePicker;

final class IssueDatePicker extends DatePicker
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Issue date'))
            ->required()
            ->default(now()->toDateString());
    }

    public static function getDefaultName(): ?string
    {
        return 'issue_date';
    }
}
