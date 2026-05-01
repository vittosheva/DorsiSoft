<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxRules\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\System\Enums\TaxAppliesToEnum;

final class TaxRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        self::identificationSection(),
                        self::validitySection(),
                    ])
                    ->columnSpan(1),

                self::conditionsSection(),
            ]);
    }

    private static function identificationSection(): Section
    {
        return Section::make(__('Identification'))
            ->afterHeader([
                Toggle::make('is_active')
                    ->default(true),
            ])
            ->schema([
                Grid::make(12)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(150)
                            ->columnSpan(7),

                        Select::make('applies_to')
                            ->options(TaxAppliesToEnum::class)
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('priority')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(9999)
                            ->default(100)
                            ->required()
                            ->columnSpan(2),

                        Textarea::make('description')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Select::make('tax_definition_id')
                            ->relationship(
                                name: 'taxDefinition',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->active()->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function conditionsSection(): Section
    {
        return Section::make(__('Conditions'))
            ->description(__('All conditions are ANDed. Leave empty to match all transactions.'))
            ->schema([
                Repeater::make('conditions')
                    ->hiddenLabel()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('field')
                                    ->label(__('Field'))
                                    ->options([
                                        'partner.identification_type' => __('Partner — Identification Type'),
                                        'partner.tax_regime' => __('Partner — Tax Regime'),
                                        'product.category' => __('Product — Category'),
                                        'product.sri_code' => __('Product — SRI Code'),
                                        'document.type' => __('Document — Type'),
                                        'company.ruc' => __('Company — RUC'),
                                        'date' => __('Date (YYYY-MM-DD)'),
                                    ])
                                    ->required()
                                    ->native(false),

                                Select::make('operator')
                                    ->options([
                                        '=' => __('= (equals)'),
                                        '!=' => __('≠ (not equals)'),
                                        'in' => __('in (any of)'),
                                        'not_in' => __('not in (none of)'),
                                        '>' => __('> (greater than)'),
                                        '>=' => __('≥ (greater or equal)'),
                                        '<' => __('< (less than)'),
                                        '<=' => __('≤ (less or equal)'),
                                        'contains' => __('contains'),
                                    ])
                                    ->required()
                                    ->native(false),

                                TextInput::make('value')
                                    ->required()
                                    ->helperText(__('For "in" / "not in" use comma-separated values')),
                            ]),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel(__('Add Condition'))
                    ->reorderable()
                    ->collapsible(),
            ]);
    }

    private static function validitySection(): Section
    {
        return Section::make(__('Validity'))
            ->schema([
                Grid::make(3)
                    ->schema([
                        DatePicker::make('valid_from')
                            ->required()
                            ->default(now()),

                        DatePicker::make('valid_to')
                            ->nullable()
                            ->after('valid_from'),
                    ]),
            ]);
    }
}
