<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\TextInputs;

use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

final class PhoneTextInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->tel()
            ->maxLength(20)
            ->prefixIcon(Heroicon::OutlinedPhone)
            ->columnSpan(3);
    }

    public static function getDefaultName(): ?string
    {
        return 'phone';
    }
}
