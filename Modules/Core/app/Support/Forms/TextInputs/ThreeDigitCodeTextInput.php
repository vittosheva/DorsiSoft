<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\TextInputs;

use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

final class ThreeDigitCodeTextInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->length(3)
            ->inputMode('numeric')
            ->mask('999')
            ->hintIcon(Heroicon::OutlinedInformationCircle, __('Must be a valid SRI code (e.g., 001, 002). The value 000 is not allowed.'))
            ->rules([
                'digits:3',
                'regex:/^[0-9]{3}$/',
                'not_in:000',
            ])
            ->validationMessages([
                'not_in' => __('The field code cannot be 000'),
            ])
            ->formatStateUsing(fn (?string $state): ?string => self::normalize($state))
            ->dehydrateStateUsing(fn (?string $state): ?string => self::normalize($state))
            ->afterStateUpdatedJs(<<<'JS'
                const normalized = ($state ?? '').replace(/[^0-9]/g, '').slice(0, 3);

                if (normalized.length === 0) {
                    $state = '';
                }

                $state = normalized.padStart(3, '0');

                if (normalized.length === 3 && $state === '000') {
                    $state = '';
                }
            JS);
    }

    public static function getDefaultName(): ?string
    {
        return 'code';
    }

    public static function normalize(?string $state): ?string
    {
        $normalized = preg_replace('/\D/', '', (string) ($state ?? ''));

        if ($normalized === null || $normalized === '') {
            return null;
        }

        return mb_str_pad(mb_substr($normalized, 0, 3), 3, '0', STR_PAD_LEFT);
    }
}
