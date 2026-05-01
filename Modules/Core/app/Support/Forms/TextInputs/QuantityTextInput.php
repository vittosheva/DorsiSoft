<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\TextInputs;

use Filament\Forms\Components\TextInput;

final class QuantityTextInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->numeric()
            ->minValue(0)
            ->nullable()
            ->suffix(__('units'));
    }

    public static function getDefaultName(): ?string
    {
        return 'quantity';
    }
}
