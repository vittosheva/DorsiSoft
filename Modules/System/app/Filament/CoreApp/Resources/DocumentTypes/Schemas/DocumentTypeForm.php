<?php

declare(strict_types=1);

namespace Modules\System\Filament\CoreApp\Resources\DocumentTypes\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;

final class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        self::identificationSection(),
                        self::accountingSection(),
                    ]),
                Grid::make(1)
                    ->schema([
                        self::behaviorsSection(),
                    ]),
            ])
            ->columns(2);
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
                        CodeTextInput::make('code')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->autoGenerateFromModel(
                                scope: fn () => [
                                    'company_id' => Filament::getTenant()?->getKey(),
                                ],
                            )
                            ->columnSpan(3),

                        NameTextInput::make()
                            ->columnSpan(7),

                        TextInput::make('sri_code')
                            ->maxLength(10)
                            ->hintIcon(Heroicon::InformationCircle, __('01=Invoice, 04=Purchase Settlement, 05=Debit Note, 06=Credit Note, 07=Withholding'))
                            ->columnSpan(2),
                    ]),
            ]);
    }

    private static function behaviorsSection(): Section
    {
        return Section::make(__('Behaviors'))
            ->description(__('Configure what this document type generates and affects.'))
            ->schema([
                Grid::make(12)
                    ->schema([
                        Toggle::make('generates_receivable')
                            ->label(__('Generates Receivable (CxC)'))
                            ->inline(false)
                            ->columnSpan(4),

                        Toggle::make('generates_payable')
                            ->label(__('Generates Payable (CxP)'))
                            ->inline(false)
                            ->columnSpan(4),

                        Toggle::make('affects_inventory')
                            ->inline(false)
                            ->columnSpan(4),

                        Toggle::make('affects_accounting')
                            ->inline(false)
                            ->columnSpan(4),

                        Toggle::make('requires_authorization')
                            ->inline(false)
                            ->columnSpan(4),

                        Toggle::make('allows_credit')
                            ->inline(false)
                            ->columnSpan(4),

                        Toggle::make('is_electronic')
                            ->inline(false)
                            ->columnSpan(4),

                        Toggle::make('is_purchase')
                            ->inline(false)
                            ->columnSpan(4),
                    ]),
            ]);
    }

    private static function accountingSection(): Section
    {
        return Section::make(__('Accounting Integration'))
            ->description(__('Default accounts used when generating draft journal entries.'))
            ->schema([
                Grid::make(12)
                    ->schema([
                        TextInput::make('default_debit_account_code')
                            ->maxLength(30)
                            ->placeholder('1.1.01.001')
                            ->columnSpan(6),

                        TextInput::make('default_credit_account_code')
                            ->maxLength(30)
                            ->placeholder('4.1.01.001')
                            ->columnSpan(6),
                    ]),
            ]);
    }
}
