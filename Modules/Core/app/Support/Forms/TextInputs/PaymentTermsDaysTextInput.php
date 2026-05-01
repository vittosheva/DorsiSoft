<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\TextInputs;

use Filament\Forms\Components\TextInput;

final class PaymentTermsDaysTextInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->integer()
            ->minValue(0)
            ->default(0)
            ->columnSpan(3);
    }

    public static function getDefaultName(): ?string
    {
        return 'payment_terms_days';
    }
}
