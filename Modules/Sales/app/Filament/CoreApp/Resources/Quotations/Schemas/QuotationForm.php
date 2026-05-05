<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Quotations\Schemas;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\DatePickers\IssueDatePicker;
use Modules\Core\Support\Forms\Selects\CurrencyCodeSelect;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Finance\Support\Forms\Selects\PriceListSelect;
use Modules\People\Support\Forms\Selects\CustomerBusinessPartnerSelect;
use Modules\People\Support\Forms\Selects\SellerUserSelect;
use Modules\Sales\Enums\DiscountTypeEnum;
use Modules\Sales\Livewire\QuotationItems;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Support\Forms\Components\DocumentStatusBadge;
use Throwable;

final class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        self::quoteDataSection()
                            ->columnSpan(5),
                        self::customerSection()
                            ->columnSpan(7),
                    ]),

                Livewire::make(QuotationItems::class, fn (?Quotation $record, string $operation) => [
                    'quotationId' => $record?->getKey(),
                    'minimumItemsCount' => 1,
                    'minimumItemsValidationMessage' => __('Add at least one item to the document.'),
                    'operation' => $operation,
                ]),

                TextInput::make('document_items_count')
                    ->hiddenLabel()
                    ->readOnly()
                    ->dehydrated(false)
                    ->rules(['integer', 'min:1'])
                    ->validationMessages(['min' => __('Add at least one item to the document.')])
                    ->extraInputAttributes(['class' => 'sr-only'])
                    ->extraAttributes(['class' => 'hidden has-[.fi-fo-field-wrp-error-message]:block']),

                Grid::make(12)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Section::make(__('Terms and conditions'))
                                    ->icon(Heroicon::ChatBubbleLeftRight)
                                    ->schema([
                                        RichEditor::make('introduction')
                                            ->hiddenLabel(),
                                    ]),
                            ])
                            ->columnSpan(7),

                        Grid::make(1)
                            ->schema([
                                self::notesSection(),
                                // self::discountSection(),
                                AuditSection::make(),
                            ])
                            ->columnSpan(5),
                    ]),
            ])
            ->columns(1);
    }

    private static function quoteDataSection(): Section
    {
        return Section::make(__('Quote Data'))
            ->icon(Heroicon::DocumentText)
            ->afterHeader(DocumentStatusBadge::make())
            ->schema([
                CodeTextInput::make('code')
                    ->autoGenerateFromModel(
                        scope: fn () => [
                            'company_id' => Filament::getTenant()?->getKey(),
                        ],
                    )
                    ->columnSpan(4),

                IssueDatePicker::make('issue_date')
                    ->afterStateUpdatedJs(self::syncExpiresAtJs(
                        'const rawDate = $state;',
                        "const days = Number.parseInt(String(\$get('validity_days') ?? ''), 10);",
                    ))
                    ->columnSpan(4),

                TextInput::make('validity_days')
                    ->required()
                    ->integer()
                    ->default(15)
                    ->minValue(1)
                    ->afterStateUpdatedJs(self::syncExpiresAtJs(
                        'const rawDate = $get(\'date\');',
                        "const days = Number.parseInt(String(\$state ?? ''), 10);",
                    ))
                    ->columnSpan(4),

                DatePicker::make('expires_at')
                    ->default(fn (Get $get): ?string => self::calculateExpiresAt($get('issue_date'), $get('validity_days')))
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(4),

                CurrencyCodeSelect::make('currency_code')
                    ->columnSpan(5),
            ])
            ->columns(12);
    }

    private static function customerSection(): Section
    {
        return Section::make(__('Customer'))
            ->icon(Heroicon::User)
            ->schema([
                CustomerBusinessPartnerSelect::make('business_partner_id')
                    ->required()
                    ->columnSpan(8),

                SellerUserSelect::make('seller_id')
                    ->columnSpan(4),

                PriceListSelect::make('price_list_id')
                    ->columnSpan(6),
            ])
            ->columns(12);
    }

    private static function discountSection(): Section
    {
        return Section::make(__('Global Discount'))
            ->icon(Heroicon::ReceiptPercent)
            ->schema([
                Select::make('discount_type')
                    ->label(__('Type'))
                    ->options(DiscountTypeEnum::class)
                    ->nullable()
                    ->columnSpan(1),

                TextInput::make('discount_value')
                    ->label(__('Value'))
                    ->numeric()
                    ->minValue(0)
                    ->nullable()
                    ->columnSpan(1),
            ])
            ->columns(2)
            ->collapsible();
    }

    private static function notesSection(): Section
    {
        return Section::make(__('Internal Notes'))
            ->icon(Heroicon::ChatBubbleBottomCenterText)
            ->schema([
                RichEditor::make('notes')
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ]);
    }

    private static function syncExpiresAtJs(string $rawDateAssignment, string $daysAssignment): string
    {
        $template = <<<'JS'
            (() => {
                __RAW_DATE_ASSIGNMENT__
                __DAYS_ASSIGNMENT__

                if (! rawDate || Number.isNaN(days) || days < 1) {
                    $set('expires_at', null);
                    return;
                }

                const raw = String(rawDate).trim().replace(/\//g, '-');
                const datePart = raw.split(' ')[0];
                const chunks = datePart.split('-');

                if (chunks.length !== 3) {
                    return;
                }

                let year;
                let month;
                let day;

                if (chunks[0].length === 4) {
                    year = Number(chunks[0]);
                    month = Number(chunks[1]);
                    day = Number(chunks[2]);
                } else {
                    day = Number(chunks[0]);
                    month = Number(chunks[1]);
                    year = Number(chunks[2]);
                }

                if (! year || ! month || ! day) {
                    return;
                }

                const expiresAt = new Date(year, month - 1, day);
                expiresAt.setHours(12, 0, 0, 0);
                expiresAt.setDate(expiresAt.getDate() + days);

                const yyyy = String(expiresAt.getFullYear());
                const mm = String(expiresAt.getMonth() + 1).padStart(2, '0');
                const dd = String(expiresAt.getDate()).padStart(2, '0');

                $set('expires_at', `${yyyy}-${mm}-${dd}`);
            })();
            JS;

        return str_replace(
            ['__RAW_DATE_ASSIGNMENT__', '__DAYS_ASSIGNMENT__'],
            [$rawDateAssignment, $daysAssignment],
            $template,
        );
    }

    private static function calculateExpiresAt(mixed $rawDate, mixed $rawDays): ?string
    {
        $days = (int) ($rawDays ?? 0);

        if ($days < 1) {
            return null;
        }

        if ($rawDate instanceof DateTimeInterface) {
            return CarbonImmutable::instance($rawDate)->addDays($days)->format('Y-m-d');
        }

        if (blank($rawDate)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $rawDate)->addDays($days)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }
}
