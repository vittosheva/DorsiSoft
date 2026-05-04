<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Forms\Sections;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Sales\Enums\SriPaymentMethodEnum;

final class SriPaymentsRepeaterSection
{
    public static function make(?string $heading = null, int $minItems = 1): Section
    {
        return Section::make($heading ?? __('Payments'))
            ->icon(Heroicon::CreditCard)
            ->afterHeader(__('Select payment methods according to the SRI catalog.'))
            ->schema([
                Repeater::make('sri_payments')
                    ->table([
                        TableColumn::make(__('Payment Method'))
                            ->markAsRequired()
                            ->width('60%'),
                        TableColumn::make(__('Amount'))
                            ->markAsRequired()
                            ->width('40%'),
                    ])
                    ->schema([
                        Select::make('method')
                            ->options(SriPaymentMethodEnum::class)
                            ->default(SriPaymentMethodEnum::Cash->value)
                            ->required(),

                        MoneyTextInput::make('amount')
                            ->currencyCode(fn (Get $get): string => $get('../../currency_code') ?? 'USD')
                            ->default(fn (Get $get): float => self::resolveDocumentItemsTotal($get))
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? round((float) $state, 2) : null)
                            ->live(onBlur: true)
                            ->step('0.01')
                            ->required(),
                    ])
                    ->afterStateUpdated(function ($state, Component $livewire, Get $get, Set $set) {
                        // Limpia error si hay al menos un cobro
                        if (is_array($state) && count($state) > 0) {
                            $livewire->resetValidation('data.sri_payments');
                        }

                        // Si se agregó un nuevo método de pago, autocompletar el monto con el total actual de los ítems
                        // Solo si el último monto es 0 o null (no si el usuario ya lo editó)
                        if (is_array($state) && count($state) > 0) {
                            $lastIndex = array_key_last($state);
                            $last = $state[$lastIndex] ?? null;
                            if ($last !== null && (empty($last['amount']) || (float) $last['amount'] === 0.0)) {
                                $itemsTotal = self::resolveDocumentItemsTotal($get);
                                $set("{$lastIndex}.amount", number_format($itemsTotal, 2, '.', ''));
                            }
                        }

                        // Forzar que todos los montos tengan 2 decimales
                        foreach ($state as $i => $row) {
                            if (isset($row['amount'])) {
                                $set("{$i}.amount", number_format((float) $row['amount'], 2, '.', ''));
                            }
                        }
                    })
                    ->hiddenLabel()
                    ->defaultItems(1)
                    ->minItems($minItems)
                    ->addActionLabel(__('Add Payment Method')),
            ]);
    }

    private static function resolveDocumentItemsTotal(Get $get): float
    {
        foreach (
            [
                'document_items_total',
                '../document_items_total',
                '../../document_items_total',
                '../../../document_items_total',
                'total',
                '../total',
                '../../total',
                '../../../total',
            ] as $path
        ) {
            $value = $get($path);

            if ($value !== null && $value !== '') {
                return round((float) $value, 2);
            }
        }

        return 0.0;
    }
}
