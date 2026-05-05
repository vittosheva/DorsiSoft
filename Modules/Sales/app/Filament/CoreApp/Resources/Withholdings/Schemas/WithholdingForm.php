<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Withholdings\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\DatePickers\IssueDatePicker;
use Modules\Core\Support\Forms\Textareas\NotesTextarea;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\BusinessPartnerResource;
use Modules\People\Support\Forms\Selects\SupplierBusinessPartnerSelect;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sales\Support\Actions\GenerateWithholdingItemsAction;
use Modules\Sales\Support\Forms\Components\ElectronicDocumentStatusBadges;
use Modules\Sales\Support\Forms\Sections\AdditionalInfoRepeaterSection;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Rules\SriDocumentNumber;
use Modules\Sri\Support\Components\FusedGroups\SequenceEmissionFusedGroup;
use Modules\System\Enums\TaxGroupEnum;
use Modules\System\Models\TaxWithholdingRate;

final class WithholdingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        self::generalDataSection()
                            ->columnSpan(6),
                        Grid::make(1)
                            ->schema([
                                Section::make(__('Supplier'))
                                    ->icon(BusinessPartnerResource::getNavigationIcon())
                                    ->schema([
                                        SupplierBusinessPartnerSelect::make('business_partner_id')
                                            ->hiddenLabel(),

                                        Grid::make(3)
                                            ->schema([
                                                Select::make('source_document_type')
                                                    ->options([
                                                        SriDocumentTypeEnum::Invoice->value => SriDocumentTypeEnum::Invoice->getLabel(),
                                                    ])
                                                    ->default(SriDocumentTypeEnum::Invoice->value)
                                                    ->required(),

                                                TextInput::make('source_document_number')
                                                    ->label(__('Document number'))
                                                    ->placeholder('001-001-000000001')
                                                    ->maxLength(17)
                                                    ->rule([new SriDocumentNumber()])
                                                    ->mask('999-999-999999999')
                                                    ->required(),

                                                DatePicker::make('source_document_date')
                                                    ->label(__('Issue date'))
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (?string $state, Get $get): void {
                                                        if (blank($state)) {
                                                            return;
                                                        }

                                                        $issueDate = $get('issue_date');

                                                        if (blank($issueDate)) {
                                                            return;
                                                        }

                                                        if (date('Y/m', strtotime($state)) !== date('Y/m', strtotime($issueDate))) {
                                                            Notification::make()
                                                                ->warning()
                                                                ->title(__('Source document date out of period'))
                                                                ->body(__('The source document date does not match the month and year of the issue date.'))
                                                                ->persistent()
                                                                ->send();
                                                        }
                                                    }),
                                            ]),
                                    ]),
                                // self::sourceDocumentSection(),
                            ])
                            ->columnSpan(6),
                    ]),

                self::withholdingItemsSection(),

                Grid::make(12)
                    ->schema([
                        Group::make()
                            ->schema([
                                AdditionalInfoRepeaterSection::make(),
                            ])
                            ->columnSpan(6),
                        Group::make()
                            ->schema([
                                Section::make(__('Internal Notes'))
                                    ->icon(Heroicon::ChatBubbleBottomCenterText)
                                    ->schema([
                                        NotesTextarea::make('notes')
                                            ->columnSpanFull(),
                                    ]),
                                AuditSection::make()
                                    ->columnSpan(1),
                            ])
                            ->columnSpan(6),
                    ]),
            ])
            ->columns(1);
    }

    private static function generalDataSection(): Section
    {
        return Section::make(__('General data'))
            ->icon(Heroicon::DocumentText)
            ->afterHeader(ElectronicDocumentStatusBadges::make())
            ->schema([
                CodeTextInput::make('code')
                    ->autoGenerateFromModel(
                        scope: fn() => [
                            'company_id' => Filament::getTenant()?->getKey(),
                        ],
                    )
                    ->columnSpan(4),

                IssueDatePicker::make('issue_date')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                        if (blank($state)) {
                            return;
                        }

                        $set('period_fiscal', date('Y/m', strtotime($state)));

                        $sourceDate = $get('source_document_date');

                        if (filled($sourceDate) && date('Y/m', strtotime($sourceDate)) !== date('Y/m', strtotime($state))) {
                            Notification::make()
                                ->warning()
                                ->title(__('Source document date out of period'))
                                ->body(__('The source document date does not match the month and year of the issue date.'))
                                ->persistent()
                                ->send();
                        }
                    })
                    ->columnSpan(4),

                TextInput::make('period_fiscal')
                    ->default(fn() => date('Y/m'))
                    ->readOnly()
                    ->dehydrated(false)
                    ->columnSpan(4),

                SequenceEmissionFusedGroup::makeForDocumentType(SriDocumentTypeEnum::Withholding),

            ])
            ->columns(12);
    }

    private static function sourceDocumentSection(): Section
    {
        return Section::make(__('Source Document'))
            ->icon(InvoiceResource::getNavigationIcon())
            ->schema([
                Select::make('source_document_type')
                    ->options([
                        SriDocumentTypeEnum::Invoice->value => SriDocumentTypeEnum::Invoice->getLabel(),
                    ])
                    ->default(SriDocumentTypeEnum::Invoice->value)
                    ->required(),

                TextInput::make('source_document_number')
                    ->label(__('Document number'))
                    ->placeholder('001-001-000000001')
                    ->maxLength(17)
                    ->rule([new SriDocumentNumber()])
                    ->mask('999-999-999999999')
                    ->required(),

                DatePicker::make('source_document_date')
                    ->label(__('Issue date'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Get $get): void {
                        if (blank($state)) {
                            return;
                        }

                        $issueDate = $get('issue_date');

                        if (blank($issueDate)) {
                            return;
                        }

                        if (date('Y/m', strtotime($state)) !== date('Y/m', strtotime($issueDate))) {
                            Notification::make()
                                ->warning()
                                ->title(__('Source document date out of period'))
                                ->body(__('The source document date does not match the month and year of the issue date.'))
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->columns(3);
    }

    private static function withholdingItemsSection(): Section
    {
        return Section::make(__('Withholding Lines'))
            ->icon(Heroicon::Calculator)
            ->headerActions([
                // GenerateWithholdingItemsAction::make(),
            ])
            ->schema([
                Repeater::make('items')
                    ->relationship('items')
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make(__('Tax'))->width('25%'),
                        TableColumn::make(__('Rate / Code'))->width('35%'),
                        TableColumn::make(__('Taxable base'))->width('15%'),
                        TableColumn::make(__('Withheld amount'))->width('15%'),
                    ])
                    ->schema([
                        Select::make('tax_type')
                            ->options(TaxGroupEnum::withholdingOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('withholding_rate_id', null))
                            ->columnSpan(2),

                        Select::make('withholding_rate_id')
                            ->required()
                            ->searchable()
                            ->live()
                            ->options(function (Get $get): array {
                                $taxType = $get('tax_type');

                                if (blank($taxType)) {
                                    return [];
                                }

                                $targetGroup = $taxType === 'IVA'
                                    ? TaxGroupEnum::Iva
                                    : TaxGroupEnum::Renta;

                                return TaxWithholdingRate::query()
                                    ->with('taxDefinition:id,tax_group,name')
                                    ->active()
                                    ->get()
                                    ->filter(fn(TaxWithholdingRate $rate) => $rate->taxDefinition?->tax_group === $targetGroup)
                                    ->mapWithKeys(fn(TaxWithholdingRate $rate): array => [
                                        $rate->id => "{$rate->sri_code} — {$rate->percentage}% — {$rate->description}",
                                    ])
                                    ->all();
                            })
                            ->afterStateUpdated(function (?int $state, Set $set, Get $get): void {
                                if (blank($state)) {
                                    return;
                                }

                                $rate = TaxWithholdingRate::find($state);

                                if (! $rate) {
                                    return;
                                }

                                $set('tax_code', $rate->sri_code);
                                $set('tax_rate', (string) $rate->percentage);

                                $base = (float) $get('base_amount');

                                if ($base > 0) {
                                    $withheld = number_format($base * (float) $rate->percentage / 100, 2, '.', '');
                                    $set('withheld_amount', $withheld);
                                }
                            })
                            ->columnSpan(5),

                        Hidden::make('tax_code')->dehydrated(),
                        Hidden::make('tax_rate')->dehydrated(),

                        MoneyTextInput::make('base_amount')
                            ->label(__('Base amount'))
                            ->currencyCode('USD')
                            ->required()
                            ->minValue(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                $rate = (float) $get('tax_rate');
                                $base = (float) $state;

                                if ($rate > 0 && $base > 0) {
                                    $set('withheld_amount', number_format($base * $rate / 100, 2, '.', ''));
                                }
                            })
                            ->columnSpan(2),

                        MoneyTextInput::make('withheld_amount')
                            ->label(__('Withheld amount'))
                            ->currencyCode('USD')
                            ->readOnly()
                            ->dehydrated()
                            ->columnSpan(2),
                    ])
                    ->columns(11)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addActionLabel(__('Add withholding'))
                    ->reorderable()
                    ->columnSpanFull(),
            ]);
    }
}
