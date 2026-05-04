<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\DatePickers\IssueDatePicker;
use Modules\Core\Support\Forms\Selects\CurrencyCodeSelect;
use Modules\Core\Support\Forms\Textareas\NotesTextarea;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Inventory\Models\Warehouse;
use Modules\People\Support\Forms\Selects\SupplierBusinessPartnerSelect;
use Modules\Sales\Livewire\PurchaseSettlementItems;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Models\WithholdingItem;
use Modules\Sales\Support\Forms\Components\ElectronicDocumentStatusBadges;
use Modules\Sales\Support\Forms\Sections\AdditionalInfoRepeaterSection;
use Modules\Sales\Support\Forms\Sections\SriPaymentsRepeaterSection;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Support\Components\FusedGroups\SequenceEmissionFusedGroup;
use Modules\Sri\Support\Forms\Concerns\HasSriEstablishmentFields;

final class PurchaseSettlementForm
{
    use HasSriEstablishmentFields;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        self::settlementDataSection()
                            ->columnSpan(6),
                        self::supplierSection()
                            ->columnSpan(6),
                    ]),

                Livewire::make(PurchaseSettlementItems::class, fn (?PurchaseSettlement $record, string $operation) => [
                    'purchaseSettlementId' => $record?->getKey(),
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

                self::withholdingFinancialSummarySection(),

                Grid::make(12)
                    ->schema([
                        Group::make()
                            ->schema([
                                SriPaymentsRepeaterSection::make(),
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
                                AdditionalInfoRepeaterSection::make(),
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

    private static function withholdingFinancialSummarySection(): Section
    {
        return Section::make(__('Financial Summary'))
            ->icon(Heroicon::Banknotes)
            ->schema([
                Grid::make(3)->schema([
                    TextEntry::make('_summary_document_total')
                        ->label(__('Document Total'))
                        ->prefix('$')
                        ->state(fn (?PurchaseSettlement $record): string => $record
                            ? number_format((float) $record->total, 2, '.', '')
                            : '0.00'),

                    TextEntry::make('_summary_total_withheld')
                        ->label(__('Total Withheld'))
                        ->prefix('$')
                        ->state(fn (?PurchaseSettlement $record): string => $record
                            ? number_format(
                                (float) WithholdingItem::query()
                                    ->whereIn('withholding_id', $record->withholdings()->select('id'))
                                    ->sum('withheld_amount'),
                                2,
                                '.',
                                ''
                            )
                            : '0.00'),

                    TextEntry::make('_summary_net_payable')
                        ->label(__('Net Payable'))
                        ->prefix('$')
                        ->state(function (?PurchaseSettlement $record): string {
                            if (! $record) {
                                return '0.00';
                            }

                            $withheld = (float) WithholdingItem::query()
                                ->whereIn('withholding_id', $record->withholdings()->select('id'))
                                ->sum('withheld_amount');

                            return number_format((float) $record->total - $withheld, 2, '.', '');
                        }),
                ]),
            ])
            ->visible(fn (?PurchaseSettlement $record, string $operation): bool => $operation === 'view' && $record !== null && $record->withholdings()->exists())
            ->collapsible();
    }

    private static function settlementDataSection(): Section
    {
        return Section::make(__('Purchase Settlement Data'))
            ->icon(Heroicon::ReceiptPercent)
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
                    ->columnSpan(4),

                CurrencyCodeSelect::make('currency_code')
                    ->columnSpan(4),

                /* Select::make('warehouse_id')
                    ->label(__('Warehouse'))
                    ->options(fn () => Warehouse::query()
                        ->where('company_id', Filament::getTenant()?->getKey())
                        ->where('is_active', true)
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->columnSpan(6), */

                SequenceEmissionFusedGroup::makeForDocumentType(SriDocumentTypeEnum::PurchaseSettlement),
            ])
            ->columns(12);
    }

    private static function supplierSection(): Section
    {
        return Section::make(__('Supplier Data'))
            ->icon(Heroicon::BuildingStorefront)
            ->schema([
                SupplierBusinessPartnerSelect::make('supplier_id')
                    ->columnSpanFull(),
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
