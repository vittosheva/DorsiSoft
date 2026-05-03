<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Schemas;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\DatePickers\IssueDatePicker;
use Modules\Core\Support\Forms\Selects\CurrencyCodeSelect;
use Modules\Core\Support\Forms\Textareas\NotesTextarea;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Finance\Enums\TaxTypeEnum;
use Modules\Finance\Models\Tax;
use Modules\Finance\Support\Forms\Selects\TaxSelect;
use Modules\People\Support\Forms\Selects\CustomerBusinessPartnerSelect;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\Tables\AuthorizedInvoicesTable;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Support\Forms\Components\ElectronicDocumentStatusBadges;
use Modules\Sales\Support\Forms\Groups\CustomerSnapshotHiddenFields;
use Modules\Sales\Support\Forms\Sections\AdditionalInfoRepeaterSection;
use Modules\Sales\Support\Forms\Sections\SriPaymentsRepeaterSection;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Support\Components\FusedGroups\SequenceEmissionFusedGroup;
use Modules\Sri\Support\Forms\Concerns\HasSriEstablishmentFields;
use ToneGabes\BetterOptions\Forms\Components\RadioCards;
use UnitEnum;

final class DebitNoteForm
{
    use HasSriEstablishmentFields;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        self::debitNoteDataSection()
                            ->columnSpan(6),
                        Group::make([
                            self::invoiceSourceModeSection(),
                            self::systemInvoiceSection(),
                            self::manualInvoiceSection(),
                        ])
                            ->columnSpan(6),
                    ]),

                Grid::make(12)
                    ->schema([
                        self::reasonsSection()
                            ->columnSpan(7),
                        self::totalsSection()
                            ->columnSpan(5),
                        TextInput::make('document_items_total')
                            ->hiddenLabel()
                            ->readOnly()
                            ->dehydrated(false)
                            ->hidden(),
                    ]),

                Grid::make(12)
                    ->schema([
                        self::notesSection()
                            ->columnSpan(7),
                        Grid::make(1)
                            ->schema([
                                self::paymentsSection(),
                                AdditionalInfoRepeaterSection::make(),
                                AuditSection::make(),
                            ])
                            ->columnSpan(5),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizePaymentData(array $data): array
    {
        $payments = self::normalizeSriPaymentsPayload($data['sri_payments'] ?? null);
        $primaryPayment = $payments[0] ?? null;

        $data['sri_payments'] = $payments === [] ? null : $payments;
        $data['payment_method'] = $primaryPayment['method'] ?? null;
        $data['payment_amount'] = $primaryPayment['amount'] ?? null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeInvoiceReferenceData(array $data): array
    {
        if (filled($data['invoice_id'] ?? null)) {
            $data['ext_invoice_code'] = null;
            $data['ext_invoice_date'] = null;
            $data['ext_invoice_auth_number'] = null;

            return $data;
        }

        if (filled($data['ext_invoice_code'] ?? null) || filled($data['ext_invoice_date'] ?? null) || filled($data['ext_invoice_auth_number'] ?? null)) {
            $data['invoice_id'] = null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeFormDataForFill(array $data): array
    {
        if (! array_key_exists('reasons', $data)) {
            $data['reasons'] = self::normalizeReasonsPayload($data['motivos'] ?? null);
        }

        $data['sri_payments'] = self::normalizeSriPaymentsPayload($data['sri_payments'] ?? null);
        $data['document_items_total'] = number_format((float) ($data['total'] ?? 0), 4, '.', '');

        return $data;
    }

    private static function debitNoteDataSection(): Section
    {
        return Section::make(__('Debit Note Data'))
            ->icon(Heroicon::DocumentText)
            ->afterHeader(ElectronicDocumentStatusBadges::make())
            ->schema([
                CodeTextInput::make('code')
                    ->autoGenerateFromModel(
                        scope: fn () => [
                            'company_id' => Filament::getTenant()?->getKey(),
                        ],
                    )
                    ->columnSpan(4),

                IssueDatePicker::make('issue_date')
                    ->columnSpan(3),

                CurrencyCodeSelect::make('currency_code')
                    ->live()
                    ->columnSpan(5),

                SequenceEmissionFusedGroup::makeForDocumentType(SriDocumentTypeEnum::DebitNote),
            ])
            ->columns(12)
            ->columnSpan(5);
    }

    private static function invoiceSourceModeSection(): Section
    {
        return Section::make(__('Modified Document (Reference Invoice)'))
            ->icon(InvoiceResource::getNavigationIcon())
            ->schema([
                RadioCards::make('invoice_source_mode')
                    ->hiddenLabel()
                    ->columns(2)
                    ->options([
                        'system' => __('From system'),
                        'manual' => __('Manual entry'),
                    ])
                    ->descriptions([
                        'system' => __('Select an authorized invoice from the system'),
                        'manual' => __('Invoice issued by another system'),
                    ])
                    ->icons([
                        'system' => Heroicon::ComputerDesktop->getIconForSize(IconSize::Large),
                        'manual' => Heroicon::PencilSquare->getIconForSize(IconSize::Large),
                    ])
                    ->dehydrated(false)
                    ->afterStateHydrated(function (RadioCards $component, ?DebitNote $record): void {
                        if (! $record instanceof DebitNote) {
                            return;
                        }

                        if (filled($record->ext_invoice_code)) {
                            $component->state('manual');
                        } elseif (filled($record->invoice_id)) {
                            $component->state('system');
                        }
                    })
                    ->required(),
            ]);
    }

    private static function systemInvoiceSection(): Section
    {
        return Section::make(__('Invoice Reference'))
            ->icon(Heroicon::DocumentMagnifyingGlass)
            ->schema([
                EmptyState::make(__('Authorized Invoice'))
                    ->description(__('Search and select the authorized invoice'))
                    ->icon(Heroicon::DocumentText)
                    ->footer([
                        self::systemInvoiceSelect(__('Select invoice')),
                    ])
                    ->visible(fn (Get $get): bool => blank($get('invoice_id')))
                    ->visibleJs(<<<'JS'
                        ! $get('invoice_id')
                    JS)
                    ->compact()
                    ->columnSpanFull(),

                TextEntry::make('invoice_selected_display')
                    ->hiddenLabel()
                    ->state(fn (Get $get): HtmlString => self::buildSelectedInvoiceCard($get('invoice_id')))
                    ->html()
                    ->visible(fn (Get $get): bool => filled($get('invoice_id')))
                    ->visibleJs(<<<'JS'
                        !! $get('invoice_id')
                    JS)
                    ->columnSpanFull(),

                self::systemInvoiceSelect(__('Change Invoice'), asLink: false)
                    ->visible(fn (Get $get): bool => filled($get('invoice_id')))
                    ->visibleJs(<<<'JS'
                        !! $get('invoice_id')
                    JS)
                    ->columnSpanFull(),

                Hidden::make('invoice_id')
                    ->required(fn (Get $get): bool => $get('invoice_source_mode') === 'system')
                    ->dehydrated(fn (Get $get): bool => $get('invoice_source_mode') === 'system'),
                ...CustomerSnapshotHiddenFields::make(customerEmailAsArray: true),
            ])
            ->visibleJs(<<<'JS'
                $get('invoice_source_mode') === 'system'
            JS);
    }

    private static function manualInvoiceSection(): Section
    {
        return Section::make(__('Customer'))
            // ->icon(Heroicon::User)
            ->schema([
                CustomerBusinessPartnerSelect::make('business_partner_id')
                    ->required(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->columnSpanFull(),

                TextInput::make('ext_invoice_code')
                    ->label(__('Invoice Number'))
                    ->placeholder('001-001-000000001')
                    ->maxLength(17)
                    ->required(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->dehydrated(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->columnSpan(3),

                DatePicker::make('ext_invoice_date')
                    ->label(__('Issue Date'))
                    ->required(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->dehydrated(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->columnSpan(3),

                TextInput::make('ext_invoice_auth_number')
                    ->label(__('Authorization Number'))
                    ->maxLength(49)
                    ->dehydrated(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->columnSpan(6),

                ...CustomerSnapshotHiddenFields::make(customerEmailAsArray: true),
            ])
            ->columns(12)
            ->visibleJs(<<<'JS'
                $get('invoice_source_mode') === 'manual'
            JS);
    }

    private static function reasonsSection(): Section
    {
        return Section::make(__('Modification Reasons'))
            ->icon(Heroicon::DocumentText)
            ->schema([
                Repeater::make('reasons')
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make(__('Reason'))
                            ->width('70%'),
                        TableColumn::make(__('Value'))
                            ->width('30%'),
                    ])
                    ->schema([
                        TextInput::make('reason')
                            ->required()
                            ->placeholder(__('Reason for the modification (e.g., late payment interest, price adjustment, etc.)'))
                            ->columnSpan(8),

                        MoneyTextInput::make('value')
                            ->currencyCode(fn (Get $get): string => $get('../../currency_code') ?? 'USD')
                            ->required()
                            ->live(onBlur: true)
                            ->columnSpan(4),
                    ])
                    ->afterStateUpdated(function (array $state, Get $get, Set $set): void {
                        self::syncDocumentTotals($get, $set, reasonsState: $state);
                    })
                    ->live()
                    ->defaultItems(1)
                    ->addActionLabel(__('Add Reason'))
                    ->reorderable(false)
                    ->rules(['array', 'min:1'])
                    ->validationMessages(['min' => __('Add at least one reason to the debit note.')])
                    ->columns(12)
                    ->columnSpanFull(),

                TextInput::make('tax_name')->hidden()->dehydrated(),
                TextInput::make('tax_rate')->hidden()->dehydrated(),
            ])
            ->columns(12);
    }

    private static function totalsSection(): Section
    {
        return Section::make(__('Totals'))
            ->icon(Heroicon::PlusCircle)
            ->schema([
                TaxSelect::make('tax_id')
                    ->label(__('Applicable VAT'))
                    ->forType(TaxTypeEnum::Iva)
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (?int $state, Get $get, Set $set): void {
                        if (! $state) {
                            $set('tax_name', null);
                            $set('tax_rate', null);
                            self::syncDocumentTotals($get, $set);

                            return;
                        }

                        $tax = Tax::find($state, ['id', 'name', 'rate']);
                        $set('tax_name', $tax?->name);
                        $set('tax_rate', $tax?->rate);
                        self::syncDocumentTotals($get, $set);
                    })
                    ->columnSpanFull(),

                MoneyTextInput::make('subtotal')
                    ->currencyCode(fn (Get $get): string => $get('currency_code') ?? 'USD')
                    ->readOnly()
                    ->default('0.00')
                    ->columnSpan(4),

                MoneyTextInput::make('tax_amount')
                    ->currencyCode(fn (Get $get): string => $get('currency_code') ?? 'USD')
                    ->readOnly()
                    ->default('0.00')
                    ->columnSpan(4),

                MoneyTextInput::make('total')
                    ->currencyCode(fn (Get $get): string => $get('currency_code') ?? 'USD')
                    ->readOnly()
                    ->default('0.00')
                    ->columnSpan(4),
            ])
            ->columns(12);
    }

    private static function paymentsSection(): Section
    {
        return SriPaymentsRepeaterSection::make(__('Payment'), 0);
    }

    private static function notesSection(): Section
    {
        return Section::make(__('Internal Notes'))
            ->icon(Heroicon::ChatBubbleBottomCenterText)
            ->schema([
                NotesTextarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    private static function systemInvoiceSelect(string $label, bool $asLink = false): ModalTableSelect
    {
        return ModalTableSelect::make('invoice_id')
            ->hiddenLabel()
            ->relationship(
                name: 'invoice',
                titleAttribute: 'code',
                modifyQueryUsing: fn ($query) => $query->select(['id', 'code'])
            )
            ->getOptionLabelUsing(fn () => '')
            ->tableConfiguration(AuthorizedInvoicesTable::class)
            ->live()
            ->afterStateUpdated(function ($state, Set $set): void {
                self::syncSelectedInvoice($state, $set);
            })
            ->selectAction(function (Action $action) use ($label, $asLink): Action {
                $action = $action
                    ->label($label)
                    ->modalHeading(__('Search invoices'))
                    ->modalSubmitActionLabel(__('To select'))
                    ->size(Size::Small);

                if ($asLink) {
                    return $action
                        ->link()
                        ->color('gray');
                }

                return $action->button();
            });
    }

    private static function syncSelectedInvoice(mixed $invoiceId, Set $set): void
    {
        if (blank($invoiceId)) {
            return;
        }

        $invoice = Invoice::with([
            'businessPartner:id,legal_name,trade_name,identification_type,identification_number,tax_address,email,phone,mobile',
        ])->find($invoiceId);

        if (! $invoice) {
            return;
        }

        $set('invoice_id', $invoice->getKey());

        $partner = $invoice->businessPartner;

        $set('business_partner_id', $invoice->business_partner_id);
        $set('customer_name', $partner?->legal_name);
        $set('customer_trade_name', $partner?->trade_name);
        $set('customer_identification_type', $partner?->identification_type);
        $set('customer_identification', $partner?->identification_number);
        $set('customer_address', $partner?->tax_address);
        $set('customer_email', $partner?->email);
        $set('customer_phone', $partner?->phone ?? $partner?->mobile);
    }

    private static function buildSelectedInvoiceCard(string|int|null $invoiceId): HtmlString
    {
        if (! $invoiceId) {
            return new HtmlString('');
        }

        $invoice = Invoice::query()
            ->with('businessPartner:id,legal_name,identification_number')
            ->select(['id', 'code', 'issue_date', 'total', 'business_partner_id', 'establishment_code', 'emission_point_code', 'sequential_number', 'document_type_id', 'company_id'])
            ->find($invoiceId);

        if (! $invoice) {
            return new HtmlString('');
        }

        $code = e($invoice->code);
        $partnerName = e($invoice->businessPartner?->legal_name ?? '—');
        $partnerRuc = e($invoice->businessPartner?->identification_number ?? '—');
        $rawDate = $invoice->getAttribute('issue_date');
        $date = $rawDate ? Carbon::parse($rawDate)->format('d/m/Y') : '—';
        $total = number_format((float) $invoice->total, 2);

        return new HtmlString(<<<HTML
            <div class="rounded-xl border border-gray-300 bg-gray-100 px-4 py-3 dark:border-warning-700 dark:bg-warning-900/20">
                <p class="text-sm font-semibold text-primary-700 dark:text-primary-400">{$code}</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">{$partnerName} &nbsp;·&nbsp; {$partnerRuc}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{$date} &nbsp;·&nbsp; \${$total}</p>
            </div>
        HTML);
    }

    private static function syncDocumentTotals(Get $get, Set $set, string $prefix = '', ?array $reasonsState = null): void
    {
        $reasons = $reasonsState !== null
            ? collect($reasonsState)->filter(fn (mixed $reason): bool => is_array($reason))->values()
            : collect($get("{$prefix}reasons") ?? [])->filter(fn (mixed $reason): bool => is_array($reason))->values();

        $subtotal = $reasons
            ->sum(fn (array $reason): float => (float) ($reason['value'] ?? 0));

        $taxRate = (float) ($get("{$prefix}tax_rate") ?? 0);
        $taxAmount = $subtotal * $taxRate / 100;
        $total = $subtotal + $taxAmount;

        $formattedSubtotal = number_format($subtotal, 2, '.', '');
        $formattedTaxAmount = number_format($taxAmount, 2, '.', '');
        $formattedTotal = number_format($total, 2, '.', '');

        $set("{$prefix}subtotal", $formattedSubtotal);
        $set("{$prefix}tax_amount", $formattedTaxAmount);
        $set("{$prefix}total", $formattedTotal);
        $set("{$prefix}document_items_total", $formattedTotal);

        $payments = $get("{$prefix}sri_payments");

        if (! is_array($payments)) {
            return;
        }

        $paymentKeys = array_keys(array_filter(
            $payments,
            static fn (mixed $payment): bool => is_array($payment),
        ));

        if (count($paymentKeys) !== 1) {
            return;
        }

        $firstPaymentKey = (string) $paymentKeys[0];
        $set("{$prefix}sri_payments.{$firstPaymentKey}.amount", $formattedTotal);
    }

    /**
     * @return list<array{method: string, amount: string}>
     */
    private static function normalizeSriPaymentsPayload(mixed $payments): array
    {
        return collect(is_array($payments) ? $payments : [])
            ->filter(static fn (mixed $payment): bool => is_array($payment) && filled($payment['method'] ?? null))
            ->map(static fn (array $payment): array => [
                'method' => self::normalizeSriPaymentMethod($payment['method'] ?? null),
                'amount' => number_format((float) ($payment['amount'] ?? 0), 2, '.', ''),
            ])
            ->filter(static fn (array $payment): bool => filled($payment['method']))
            ->values()
            ->all();
    }

    private static function normalizeSriPaymentMethod(mixed $method): ?string
    {
        if ($method instanceof BackedEnum) {
            return (string) $method->value;
        }

        if ($method instanceof UnitEnum) {
            return $method->name;
        }

        if (blank($method)) {
            return null;
        }

        return (string) $method;
    }

    /**
     * @return list<array{reason: string, value: string}>
     */
    private static function normalizeReasonsPayload(mixed $reasons): array
    {
        return collect(is_array($reasons) ? $reasons : [])
            ->filter(static fn (mixed $reason): bool => is_array($reason))
            ->map(static fn (array $reason): array => [
                'reason' => (string) ($reason['reason'] ?? ''),
                'value' => number_format((float) ($reason['value'] ?? 0), 4, '.', ''),
            ])
            ->values()
            ->all();
    }
}
