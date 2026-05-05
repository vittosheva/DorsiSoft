<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\DatePickers\IssueDatePicker;
use Modules\Core\Support\Forms\Selects\CurrencyCodeSelect;
use Modules\Core\Support\Forms\Textareas\NotesTextarea;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Finance\Support\Forms\Selects\PriceListSelect;
use Modules\Inventory\Models\Warehouse;
use Modules\People\Support\Forms\Selects\CustomerBusinessPartnerSelect;
use Modules\People\Support\Forms\Selects\SellerUserSelect;
use Modules\Sales\Livewire\InvoiceItems;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Support\Forms\Components\ElectronicDocumentStatusBadges;
use Modules\Sales\Support\Forms\Sections\AdditionalInfoRepeaterSection;
use Modules\Sales\Support\Forms\Sections\SriPaymentsRepeaterSection;
use Modules\Sales\Support\Forms\Selects\SalesOrderSelect;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Support\Components\FusedGroups\SequenceEmissionFusedGroup;
use Modules\Sri\Support\Forms\Concerns\HasSriEstablishmentFields;

final class InvoiceForm
{
    use HasSriEstablishmentFields;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        self::invoiceDataSection()
                            ->columnSpan(6),
                        self::customerSection()
                            ->columnSpan(6),
                    ]),

                Livewire::make(InvoiceItems::class, fn (?Invoice $record, string $operation) => [
                    'invoiceId' => $record?->getKey(),
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

                Grid::make(12)
                    ->schema([
                        Group::make()
                            ->schema([
                                AdditionalInfoRepeaterSection::make(),
                                Section::make(__('Internal Notes'))
                                    ->icon(Heroicon::ChatBubbleBottomCenterText)
                                    ->schema([
                                        NotesTextarea::make('notes')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpan(6),
                        Group::make()
                            ->schema([
                                SriPaymentsRepeaterSection::make(),
                                AuditSection::make()
                                    ->columnSpan(1),
                            ])
                            ->columnSpan(6),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * Normaliza los datos del formulario para el fill, asegurando 2 decimales en sri_payments.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeFormDataForFill(array $data): array
    {
        $data['sri_payments'] = self::normalizeSriPaymentsPayload($data['sri_payments'] ?? null);

        return $data;
    }

    private static function invoiceDataSection(): Section
    {
        return Section::make(__('Invoice Data'))
            ->icon(Heroicon::DocumentText)
            ->afterHeader(ElectronicDocumentStatusBadges::make(
                resolveExtraBadgesUsing: fn (Invoice $record): array => array_values(array_filter([
                    [
                        'key' => 'payment',
                        'title' => __('Payment'),
                        'label' => match ($record->paymentStatus()) {
                            'paid' => __('Paid'),
                            'partially_paid' => __('Partially paid'),
                            default => __('Unpaid'),
                        },
                        'color' => match ($record->paymentStatus()) {
                            'paid' => 'success',
                            'partially_paid' => 'warning',
                            default => 'gray',
                        },
                        'order' => 30,
                    ],
                    filled($record->settlementSourceLabel()) ? [
                        'key' => 'settlement_source',
                        'title' => __('Settlement'),
                        'label' => $record->settlementSourceLabel(),
                        'color' => match ($record->settlementSource()) {
                            'credit_note' => 'info',
                            'mixed' => 'warning',
                            default => 'success',
                        },
                        'order' => 31,
                    ] : null,
                ])),
            ))
            ->schema([
                CodeTextInput::make('code')
                    ->autoGenerateFromModel(
                        scope: fn () => [
                            'company_id' => Filament::getTenant()?->getKey(),
                        ],
                    )
                    ->columnSpan(4),

                IssueDatePicker::make('issue_date')
                    ->columnSpan(4),

                DatePicker::make('due_date')
                    ->nullable()
                    ->columnSpan(4),

                SequenceEmissionFusedGroup::makeForDocumentType(SriDocumentTypeEnum::Invoice),

                /* Select::make('warehouse_id')
                    ->label(__('Warehouse'))
                    ->options(fn () => Warehouse::query()
                        ->where('company_id', Filament::getTenant()?->getKey())
                        ->where('is_active', true)
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->columnSpan(6), */
            ])
            ->columns(12);
    }

    private static function customerSection(): Section
    {
        return Section::make(__('Customer'))
            ->icon(Heroicon::User)
            ->schema([
                CustomerBusinessPartnerSelect::make('business_partner_id')
                    ->columnSpan(8),
                SalesOrderSelect::make('sales_order_id')
                    ->afterStateUpdated(function ($state, Set $set, Component $livewire) {
                        if (! $state) {
                            return;
                        }

                        $order = SalesOrder::query()
                            ->select(['id', 'business_partner_id', 'currency_code'])
                            ->find($state);

                        if (! $order) {
                            return;
                        }

                        $set('business_partner_id', $order->business_partner_id);
                        $set('currency_code', $order->currency_code);
                        $livewire->dispatch('invoice-items:load-from-order', orderId: (int) $state);
                    })
                    ->columnSpan(4),
                PriceListSelect::make('price_list_id')
                    ->columnSpan(6),
                SellerUserSelect::make('seller_id')
                    ->columnSpan(4),
                CurrencyCodeSelect::make('currency_code')
                    ->columnSpan(4)
                    ->hidden(),
            ])
            ->columns(12);
    }

    /**
     * Normaliza los montos de sri_payments a 2 decimales.
     *
     * @param  mixed  $payments
     * @return array<int, array{method: string, amount: string}>
     */
    private static function normalizeSriPaymentsPayload($payments): array
    {
        return collect(is_array($payments) ? $payments : [])
            ->filter(static fn ($payment) => is_array($payment) && ! empty($payment['method']))
            ->map(static fn ($payment) => [
                'method' => $payment['method'],
                'amount' => number_format((float) ($payment['amount'] ?? 0), 2, '.', ''),
            ])
            ->filter(static fn ($payment) => ! empty($payment['method']))
            ->values()
            ->all();
    }
}
