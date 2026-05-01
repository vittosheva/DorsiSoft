<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Taxes\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;
use Modules\Finance\Enums\TaxTypeEnum;
use Modules\Finance\Models\Tax;
use Modules\System\Enums\TaxCalculationTypeEnum;
use Modules\System\Models\TaxDefinition;

final class TaxForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::infoSection(),
            ])
            ->columns(1);
    }

    private static function infoSection(): Section
    {
        return Section::make(__('Tax Information'))
            ->schema([
                Grid::make(12)
                    ->schema([
                        CodeTextInput::make()
                            ->autoGenerateFromModel(
                                modelClass: Tax::class,
                                prefix: Tax::getCodePrefix(),
                                scope: fn () => ['company_id' => Filament::getTenant()?->getKey()],
                                ignoreDeleted: false,
                            )
                            ->columnSpan(3),

                        NameTextInput::make()
                            ->tenantScopedUnique()
                            ->autofocus()
                            ->columnSpan(6),

                        Select::make('type')
                            ->options(TaxTypeEnum::options())
                            ->in(TaxTypeEnum::values())
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state === TaxTypeEnum::Iva->value) {
                                    $set('sri_code', '2');
                                    $set('calculation_type', TaxCalculationTypeEnum::Percentage->value);
                                }

                                if ($state === TaxTypeEnum::Ice->value) {
                                    $set('sri_code', '3');
                                }
                            })
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('sri_code')
                            ->label(__('SRI Code'))
                            ->maxLength(10)
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('sri_percentage_code')
                            ->label(__('SRI Percentage Code'))
                            ->maxLength(20)
                            ->required()
                            ->columnSpan(3),

                        Select::make('calculation_type')
                            ->options(TaxCalculationTypeEnum::options())
                            ->in(TaxCalculationTypeEnum::values())
                            ->default(TaxCalculationTypeEnum::Percentage->value)
                            ->required()
                            ->columnSpan(4),

                        TextInput::make('rate')
                            ->numeric()
                            ->rule('decimal:0,4')
                            ->minValue(0)
                            ->step(0.0001)
                            ->suffix(fn (callable $get): string => $get('calculation_type') === TaxCalculationTypeEnum::Fixed->value ? '$' : '%')
                            ->helperText(__('Use percentage for IVA and ad-valorem ICE. Use fixed value for specific ICE.'))
                            ->required()
                            ->columnSpan(3),

                        Textarea::make('description')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Select::make('tax_definition_id')
                            ->relationship('definition', 'name')
                            ->options(
                                fn (): array => TaxDefinition::query()->active()
                                    ->orderBy('tax_group')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(6),

                        Toggle::make('is_default')
                            ->live()
                            ->afterStateUpdated(function (?bool $state, Set $set): void {
                                if ($state) {
                                    $set('is_active', true);
                                }
                            })
                            ->inline(false)
                            ->columnSpan(3),

                        Toggle::make('is_active')
                            ->default(true)
                            ->inline(false)
                            ->disabled(fn (callable $get): bool => (bool) $get('is_default'))
                            ->dehydrated()
                            ->columnSpan(3),
                    ]),
            ]);
    }
}
