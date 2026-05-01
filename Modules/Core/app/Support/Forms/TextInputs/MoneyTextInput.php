<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\TextInputs;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Currency;

final class MoneyTextInput extends TextInput
{
    protected string|Closure|null $numberFormat = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->inputMode('decimal')
            ->rule('numeric')
            ->step('0.01')
            ->minValue(0)
            ->placeholder('0.00')
            ->afterStateHydrated(function ($state, $component): void {
                if (filled($state)) {
                    $component->state(number_format((float) $state, 2, '.', ''));
                }
            })
            ->formatStateUsing(fn ($state): string => filled($state) ? number_format((float) $state, 2, '.', '') : '')
            ->extraInputAttributes([
                'class' => 'text-right money-text-input',
                'data-money-format' => '', // marcador para el JS global
                'x-on:keydown' => "const allowed = ['0','1','2','3','4','5','6','7','8','9','.','Backspace','Delete','Tab','Enter','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End']; if (!allowed.includes(\$event.key) && !\$event.ctrlKey && !\$event.metaKey) \$event.preventDefault()",
            ]);
    }

    /**
     * Resolve the currency symbol for a given ISO 4217 code.
     * Result is cached permanently (following the rememberForever pattern).
     */
    public static function symbolForCode(?string $currencyCode): string
    {
        if (blank($currencyCode)) {
            return '$';
        }

        $cacheKey = "currency_symbol.{$currencyCode}";

        return Cache::rememberForever(
            $cacheKey,
            fn (): string => Currency::query()
                ->where('code', $currencyCode)
                ->value('symbol') ?? '$',
        );
    }

    /**
     * Define el formato numérico a usar (locale, currency, etc).
     * Puede ser string (locale), array de opciones, o closure.
     * Ejemplo: ->numberFormat('es-ES') o ->numberFormat(fn ($state, $get) => ...)
     */
    public function numberFormat(string|array|Closure $format): static
    {
        $this->numberFormat = $format;
        $this->extraInputAttributes(function () {
            $format = $this->evaluate($this->numberFormat);
            $attrs = [];
            if (is_string($format)) {
                $attrs['data-locale'] = $format;
            } elseif (is_array($format)) {
                if (isset($format['locale'])) {
                    $attrs['data-locale'] = $format['locale'];
                }
                if (isset($format['currency'])) {
                    $attrs['data-currency'] = $format['currency'];
                }
            }

            return $attrs;
        });

        return $this;
    }

    /**
     * Set the currency code used to resolve the prefix symbol.
     * Accepts a static ISO 4217 code string or a closure (e.g. using Get).
     */
    public function currencyCode(string|Closure $code): static
    {
        $this->prefix(function () use ($code): string {
            $resolved = $this->evaluate($code);

            return self::symbolForCode(is_string($resolved) ? $resolved : null);
        });

        return $this;
    }
}
