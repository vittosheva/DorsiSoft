<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Schemas;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\DatePickers\IssueDatePicker;
use Modules\Core\Support\Forms\Selects\CurrencyCodeSelect;
use Modules\Core\Support\Forms\Textareas\NotesTextarea;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\People\Support\Forms\Selects\CustomerBusinessPartnerSelect;
use Modules\Sales\Enums\CreditNoteReasonEnum;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\Tables\AuthorizedInvoicesTable;
use Modules\Sales\Livewire\CreditNoteItems;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Support\Forms\Components\ElectronicDocumentStatusBadges;
use Modules\Sales\Support\Forms\Sections\AdditionalInfoRepeaterSection;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Rules\NotAllZerosPerBlock;
use Modules\Sri\Support\Components\FusedGroups\SequenceEmissionFusedGroup;
use Modules\Sri\Support\Forms\Concerns\HasSriEstablishmentFields;
use ToneGabes\BetterOptions\Forms\Components\RadioCards;

final class CreditNoteForm
{
    use HasSriEstablishmentFields;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        Group::make([
                            self::creditNoteDataSection(),
                        ])
                            ->columnSpan(6),
                        Group::make([
                            self::invoiceSourceModeSection(),
                            self::systemInvoiceSection(),
                            self::manualInvoiceSection(),
                        ])
                            ->columnSpan(6),
                    ]),

                Livewire::make(CreditNoteItems::class, fn (?CreditNote $record, string $operation) => [
                    'creditNoteId' => $record?->getKey(),
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

                TextInput::make('document_items_total')
                    ->hiddenLabel()
                    ->readOnly()
                    ->dehydrated(false)
                    ->hidden(),

                Grid::make(2)
                    ->schema([
                        self::notesSection(),
                        Grid::make(1)
                            ->schema([
                                AdditionalInfoRepeaterSection::make(),
                                AuditSection::make(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeReasonData(array $data): array
    {
        $reasonCode = self::parseReasonCode($data['reason_code'] ?? null);

        if ($reasonCode === null) {
            return $data;
        }

        if ($reasonCode !== CreditNoteReasonEnum::Other) {
            $data['reason'] = $reasonCode->getDescription();

            return $data;
        }

        $reason = is_string($data['reason'] ?? null) ? mb_trim($data['reason']) : null;

        if (blank($reason)) {
            throw ValidationException::withMessages([
                'data.reason' => __('The description field is required when the reason is Other.'),
            ]);
        }

        $data['reason'] = $reason;

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

    private static function creditNoteDataSection(): Section
    {
        return Section::make(__('Credit Note Data'))
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
                    ->columnSpan(5),

                SequenceEmissionFusedGroup::makeForDocumentType(SriDocumentTypeEnum::CreditNote)
                    ->columnSpanFull(),

                Select::make('reason_code')
                    ->options(CreditNoteReasonEnum::class)
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Get $get, Set $set): void {
                        $reasonCode = self::parseReasonCode($state);

                        if ($reasonCode === null) {
                            $set('reason', null);

                            return;
                        }

                        if ($reasonCode === CreditNoteReasonEnum::Other) {
                            $set('reason', filled($get('reason')) ? mb_trim((string) $get('reason')) : '');

                            return;
                        }

                        $set('reason', $reasonCode->getDescription());
                    })
                    ->required()
                    ->columnSpan(6),

                Textarea::make('reason')
                    ->label(__('Description'))
                    ->rows(3)
                    ->maxLength(500)
                    ->dehydrated(true)
                    ->required(fn (Get $get): bool => self::isOtherReasonCode($get('reason_code')))
                    ->visible(fn (Get $get): bool => self::isOtherReasonCode($get('reason_code')))
                    ->columnSpan(6),
            ])
            ->columns(12);
    }

    private static function invoiceSourceModeSection(): Section
    {
        return Section::make(__('Modified Document (Reference Invoice)'))
            ->icon(InvoiceResource::getNavigationIcon())
            ->hiddenLabel()
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
                    ->afterStateHydrated(function (RadioCards $component, ?CreditNote $record): void {
                        if (! $record instanceof CreditNote) {
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

                // Hidden snapshot fields — always in Livewire state regardless of section visibility
                Hidden::make('invoice_id')
                    ->required(fn (Get $get): bool => $get('invoice_source_mode') === 'system')
                    ->dehydrated(fn (Get $get): bool => $get('invoice_source_mode') === 'system')
                    ->rule(
                        fn (Get $get, ?CreditNote $record) => $get('invoice_source_mode') === 'system'
                            ? Rule::unique('sales_credit_notes', 'invoice_id')
                                ->where('company_id', Filament::getTenant()?->getKey())
                                ->whereNot('status', CreditNoteStatusEnum::Voided->value)
                                ->where('electronic_status', ElectronicStatusEnum::Authorized->value)
                                ->ignore($record?->getKey())
                            : 'nullable'
                    )
                    ->validationMessages([
                        'unique' => __('This invoice already has an active credit note.'),
                    ]),
            ])
            ->visibleJs(<<<'JS'
                $get('invoice_source_mode') === 'system'
            JS);
    }

    private static function manualInvoiceSection(): Section
    {
        return Section::make(__('Manual Invoice Reference'))
            ->icon(Heroicon::PencilSquare)
            ->schema([
                CustomerBusinessPartnerSelect::make('business_partner_id')
                    ->required(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->columnSpanFull(),

                TextInput::make('ext_invoice_code')
                    ->label(__('Invoice Number'))
                    ->placeholder('001-001-000000001')
                    ->live(onBlur: true)
                    ->mask('999-999-999999999')
                    ->rule(fn (Get $get) => $get('invoice_source_mode') === 'manual' ? new NotAllZerosPerBlock : 'nullable')
                    ->trim()
                    ->minLength(17)
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
                    ->mask('99999999999999999999999999999999999999999999999') // 49 digits
                    ->placeholder(__('49 digits — optional'))
                    ->live(onBlur: true)
                    ->trim()
                    ->minLength(49)
                    ->maxLength(49)
                    ->dehydrated(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->columnSpan(6),
            ])
            ->columns(12)
            ->visibleJs(<<<'JS'
                $get('invoice_source_mode') === 'manual'
            JS);
    }

    private static function customerSection(): Section
    {
        return Section::make(__('Customer'))
            ->icon(Heroicon::User)
            ->schema([
                CustomerBusinessPartnerSelect::make('business_partner_id')
                    ->columnSpanFull(),

                TextInput::make('ext_invoice_code')
                    ->label(__('Invoice Code'))
                    ->placeholder('001-001-000000001')
                    ->maxLength(17)
                    ->visible(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->columnSpan(4),

                DatePicker::make('ext_invoice_date')
                    ->label(__('Invoice Date'))
                    ->visible(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->columnSpan(4),

                TextInput::make('ext_invoice_auth_number')
                    ->label(__('Authorization Number'))
                    ->maxLength(49)
                    ->visible(fn (Get $get): bool => $get('invoice_source_mode') === 'manual')
                    ->columnSpan(8),

            ])
            ->columns(12);
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
            ->afterStateUpdated(function ($state, Set $set, Component $livewire): void {
                self::syncSelectedInvoice($state, $set, $livewire);
            })
            ->selectAction(function (Action $action) use ($label, $asLink): Action {
                $action = $action
                    ->label($label)
                    ->modalHeading(__('Search invoices'))
                    ->modalSubmitActionLabel(__('Confirm selection'))
                    ->size(Size::Small);

                if ($asLink) {
                    return $action
                        ->link()
                        ->color('gray');
                }

                return $action->button();
            });
    }

    private static function parseReasonCode(mixed $reasonCode): ?CreditNoteReasonEnum
    {
        if ($reasonCode instanceof CreditNoteReasonEnum) {
            return $reasonCode;
        }

        if (is_string($reasonCode)) {
            return CreditNoteReasonEnum::tryFrom($reasonCode);
        }

        return null;
    }

    private static function isOtherReasonCode(mixed $reasonCode): bool
    {
        return self::parseReasonCode($reasonCode) === CreditNoteReasonEnum::Other;
    }

    private static function syncSelectedInvoice(mixed $invoiceId, Set $set, Component $livewire): void
    {
        if (blank($invoiceId)) {
            return;
        }

        $invoice = Invoice::with([
            'businessPartner:id,legal_name,trade_name,identification_type,identification_number,tax_address,email,phone,mobile',
        ])
            ->find($invoiceId);

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

        $livewire->dispatch('credit-note-items:load-from-invoice', invoiceId: $invoice->getKey());
    }

    private static function buildSelectedInvoiceCard(string|int|null $invoiceId): HtmlString
    {
        if (! $invoiceId) {
            return new HtmlString('');
        }

        $invoice = Invoice::query()
            ->with(['businessPartner:id,legal_name,identification_number'])
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
}
