<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Enums\TaxBaseTypeEnum;
use Modules\System\Enums\TaxCalculationTypeEnum;
use Modules\System\Enums\TaxGroupEnum;
use Modules\System\Enums\TaxNatureEnum;
use Modules\System\Models\TaxCatalog;

final class TaxDefinitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        self::identificationSection(),
                        self::behaviorSection(),
                    ]),
                Grid::make(1)
                    ->schema([
                        self::classificationSection(),
                        self::calculationSection(),
                        self::sriSection(),
                    ]),
            ])
            ->columns(2);
    }

    private static function identificationSection(): Section
    {
        return Section::make(__('Identification'))
            ->schema([
                Grid::make(12)
                    ->schema([
                        CodeTextInput::make()
                            ->unique(ignoreRecord: true)
                            ->columnSpan(3),

                        NameTextInput::make()
                            ->unique(ignoreRecord: true)
                            ->autofocus()
                            ->columnSpan(9),

                        Select::make('tax_catalog_id')
                            ->options(fn () => TaxCatalog::query()->active()->orderBy('sort_order')->pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->columnSpan(6),

                        Textarea::make('description')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function classificationSection(): Section
    {
        return Section::make(__('Classification'))
            ->schema([
                Grid::make(12)
                    ->schema([
                        Select::make('tax_group')
                            ->options(TaxGroupEnum::options())
                            ->in(TaxGroupEnum::values())
                            ->required()
                            ->columnSpan(4),

                        Select::make('tax_type')
                            ->options(TaxNatureEnum::options())
                            ->in(TaxNatureEnum::values())
                            ->required()
                            ->columnSpan(4),

                        Select::make('applies_to')
                            ->options(TaxAppliesToEnum::options())
                            ->in(TaxAppliesToEnum::values())
                            ->default(TaxAppliesToEnum::Ambos->value)
                            ->required()
                            ->columnSpan(4),
                    ]),
            ]);
    }

    private static function calculationSection(): Section
    {
        return Section::make(__('Calculation'))
            ->schema([
                Grid::make(12)
                    ->schema([
                        Select::make('calculation_type')
                            ->options(TaxCalculationTypeEnum::options())
                            ->in(TaxCalculationTypeEnum::values())
                            ->default(TaxCalculationTypeEnum::Percentage->value)
                            ->required()
                            ->columnSpan(4),

                        Select::make('base_type')
                            ->label(__('Tax Base'))
                            ->options(TaxBaseTypeEnum::options())
                            ->in(TaxBaseTypeEnum::values())
                            ->default(TaxBaseTypeEnum::Precio->value)
                            ->required()
                            ->columnSpan(4),

                        TextInput::make('rate')
                            ->label(__('Rate (%)'))
                            ->numeric()
                            ->rule('decimal:0,4')
                            ->minValue(0)
                            ->step(0.0001)
                            ->columnSpan(2),

                        TextInput::make('fixed_amount')
                            ->numeric()
                            ->rule('decimal:0,4')
                            ->minValue(0)
                            ->prefix('$')
                            ->columnSpan(2),
                    ]),
            ]);
    }

    private static function sriSection(): Section
    {
        return Section::make(__('SRI Codes'))
            ->schema([
                Grid::make(12)
                    ->schema([
                        TextInput::make('sri_code')
                            ->maxLength(10)
                            ->columnSpan(3),

                        TextInput::make('sri_percentage_code')
                            ->maxLength(10)
                            ->columnSpan(3),

                        DatePicker::make('valid_from')
                            ->required()
                            ->default(now())
                            ->columnSpan(3),

                        DatePicker::make('valid_to')
                            ->columnSpan(3),
                    ]),
            ]);
    }

    private static function behaviorSection(): Section
    {
        return Section::make(__('Behavior'))
            ->schema([
                Grid::make(12)
                    ->schema([
                        Toggle::make('is_exempt')
                            ->inline(false)
                            ->columnSpan(3),

                        Toggle::make('is_zero_rate')
                            ->inline(false)
                            ->columnSpan(3),

                        Toggle::make('is_withholding')
                            ->inline(false)
                            ->columnSpan(3),

                        Toggle::make('is_active')
                            ->inline(false)
                            ->default(true)
                            ->columnSpan(3),
                    ]),
            ]);
    }
}
