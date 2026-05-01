<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Collections\Schemas;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\Selects\CurrencyCodeSelect;
use Modules\Core\Support\Forms\Textareas\NotesTextarea;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Finance\Enums\CollectionMethodEnum;
use Modules\Finance\Enums\CollectionStatusEnum;
use Modules\Finance\Models\Collection;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\BusinessPartnerResource;
use Modules\People\Support\Forms\Selects\CustomerBusinessPartnerSelect;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Support\Forms\Components\DocumentStatusBadge;
use Modules\Sri\Enums\ElectronicStatusEnum;

final class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                self::collectionDataSection(),
                                self::notesSection(),
                            ])
                            ->columns(1),
                        Grid::make(2)
                            ->schema([
                                self::customerSection(),
                                self::allocationsSection(),
                                AuditSection::make(),
                            ])
                            ->columns(1),
                    ]),
            ])
            ->columns(1);
    }

    private static function collectionDataSection(): Section
    {
        return Section::make(__('Collection Data'))
            ->icon(Heroicon::Banknotes)
            ->afterHeader(DocumentStatusBadge::make(resolveStateUsing: fn (?Collection $record): ?CollectionStatusEnum => match (true) {
                ! $record instanceof Collection => null,
                $record->isVoided() => CollectionStatusEnum::Voided,
                default => CollectionStatusEnum::Active,
            }))
            ->schema([
                CodeTextInput::make()
                    ->autoGenerateFromModel(
                        modelClass: Collection::class,
                        prefix: fn (): string => Collection::getCodePrefix().'-'.now()->year.'-',
                        padding: 6,
                        scope: fn () => ['company_id' => Filament::getTenant()?->getKey()],
                        ignoreDeleted: false,
                    ),

                DatePicker::make('collection_date')
                    ->required()
                    ->default(now()->toDateString())
                    ->autofocus(),

                MoneyTextInput::make('amount')
                    ->currencyCode(fn (Get $get): string => $get('currency_code') ?? 'USD')
                    ->live(onBlur: true)
                    ->required(),

                CurrencyCodeSelect::make('currency_code')
                    ->required(),

                Select::make('collection_method')
                    ->options(CollectionMethodEnum::class)
                    ->live()
                    ->required(),

                TextInput::make('reference_number')
                    ->nullable()
                    ->maxLength(100),

                Select::make('credit_note_id')
                    ->label(__('Credit Note'))
                    ->options(fn (Get $get): array => self::resolveCreditNoteOptions(
                        businessPartnerId: $get('business_partner_id'),
                    ))
                    ->getOptionLabelUsing(function (mixed $value): ?string {
                        if (blank($value)) {
                            return null;
                        }

                        $nc = CreditNote::query()->select(['id', 'code', 'customer_name', 'total', 'refunded_amount'])->find($value);

                        if (! $nc) {
                            return null;
                        }

                        return self::formatCreditNoteLabel($nc);
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if (blank($state)) {
                            return;
                        }

                        $nc = CreditNote::query()->select(['id', 'total', 'refunded_amount', 'currency_code'])->find($state);

                        if (! $nc) {
                            return;
                        }

                        $set('amount', $nc->getAvailableCollectionBalance());
                        $set('currency_code', $nc->currency_code);
                    })
                    ->visible(fn (Get $get): bool => $get('collection_method') === CollectionMethodEnum::CreditNote)
                    ->required(fn (Get $get): bool => $get('collection_method') === CollectionMethodEnum::CreditNote)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    private static function customerSection(): Section
    {
        return Section::make(__('Customer'))
            ->icon(BusinessPartnerResource::getNavigationIcon())
            ->schema([
                CustomerBusinessPartnerSelect::make('business_partner_id')
                    ->required()
                    ->columnSpanFull(),

                // Hidden snapshot populated via afterStateUpdated
                TextInput::make('customer_name')->hidden()->dehydrated(),
            ]);
    }

    private static function notesSection(): Section
    {
        return Section::make(__('Notes'))
            ->icon(Heroicon::ChatBubbleBottomCenterText)
            ->schema([
                NotesTextarea::make('notes')
                    ->columnSpanFull(),
            ])
            ->collapsible();
    }

    private static function allocationsSection(): Section
    {
        return Section::make(__('Invoice Allocations'))
            ->icon(Heroicon::ReceiptPercent)
            ->visibleOn(Operation::Create)
            ->visible(
                fn (Get $get): bool => filled($get('business_partner_id'))
                    && is_numeric($get('amount'))
                    && (float) $get('amount') > 0
                    && $get('collection_method') !== CollectionMethodEnum::CreditNote->value
            )
            ->visibleJs(<<<'JS'
                const businessPartnerId = $get('business_partner_id')
                const amount = Number.parseFloat(String($get('amount') ?? '').replace(',', '.'))
                const method = $get('collection_method')

                return !! businessPartnerId && Number.isFinite(amount) && amount > 0 && method !== 'credit_note'
            JS)
            ->schema([
                Repeater::make('allocation_items')
                    ->label(__('Invoices'))
                    ->schema([
                        Select::make('invoice_id')
                            ->options(fn (Get $get): array => self::resolveInvoiceOptions(
                                businessPartnerId: $get('../../business_partner_id'),
                                allocationItems: $get('../../allocation_items') ?? [],
                                currentInvoiceId: $get('invoice_id'),
                            ))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if (blank($state)) {
                                    return;
                                }

                                $invoice = Invoice::query()->select(['id', 'total', 'paid_amount'])->find($state);

                                if (! $invoice) {
                                    return;
                                }

                                $set('amount', CollectionAllocationMath::pending($invoice->total, $invoice->paid_amount));
                            })
                            ->columnSpan(8),

                        MoneyTextInput::make('amount')
                            ->label(__('Allocated'))
                            ->currencyCode(fn (Get $get): string => $get('../../currency_code') ?? 'USD')
                            ->required()
                            ->minValue(0.0001)
                            ->suffixAction(
                                Action::make('set_invoice_pending_amount')
                                    ->tooltip(__('Apply maximum'))
                                    ->icon(Heroicon::Bolt)
                                    ->action(function (Get $get, Set $set): void {
                                        $invoiceId = $get('invoice_id');

                                        if (blank($invoiceId)) {
                                            Notification::make()
                                                ->title(__('No invoice selected'))
                                                ->body(__('Please select an invoice to apply the maximum pending amount.'))
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        $invoice = Invoice::query()->select(['id', 'total', 'paid_amount'])->find($invoiceId);

                                        if (! $invoice) {
                                            Notification::make()
                                                ->title(__('Invoice not found'))
                                                ->body(__('The selected invoice could not be found.'))
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        $pendingInvoiceAmount = CollectionAllocationMath::pending($invoice->total, $invoice->paid_amount);
                                        $remainingCollectionAmount = self::resolveRemainingCollectionAmount(
                                            collectionAmount: $get('../../amount'),
                                            allocationItems: $get('../../allocation_items') ?? [],
                                            currentItemAmount: $get('amount'),
                                        );

                                        if (bccomp($remainingCollectionAmount, $pendingInvoiceAmount, CollectionAllocationMath::SCALE) <= 0) {
                                            $set('amount', $remainingCollectionAmount);
                                            Notification::make()
                                                ->title(__('Maximum applied'))
                                                ->body(__('The allocated amount has been set to the remaining collection amount, which is sufficient to cover the pending invoice amount.'))
                                                ->success()
                                                ->send();

                                            return;
                                        }

                                        $set('amount', $pendingInvoiceAmount);
                                    })
                            )
                            ->columnSpan(4),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel(__('Add Invoice'))
                    ->columns(12)
                    ->columnSpanFull(),
            ])
            ->hiddenOn(Operation::View);
    }

    /**
     * @return array<int, string>
     */
    private static function resolveCreditNoteOptions(mixed $businessPartnerId): array
    {
        if (blank($businessPartnerId)) {
            return [];
        }

        return CreditNote::query()
            ->select(['id', 'code', 'customer_name', 'total', 'refunded_amount'])
            ->where('business_partner_id', (int) $businessPartnerId)
            ->whereNotIn('status', [CreditNoteStatusEnum::Draft->value, CreditNoteStatusEnum::Voided->value])
            ->where('electronic_status', ElectronicStatusEnum::Authorized->value)
            ->whereNotIn('id', function ($query): void {
                $query->select('credit_note_id')
                    ->from('sales_collections')
                    ->whereNotNull('credit_note_id')
                    ->whereNull('voided_at');
            })
            ->orderBy('code')
            ->limit(25)
            ->get()
            ->map(function (CreditNote $nc): array {
                return [
                    'nc' => $nc,
                    'balance' => $nc->getAvailableCollectionBalance(),
                ];
            })
            ->filter(fn (array $item): bool => bccomp($item['balance'], '0.0000', CollectionAllocationMath::SCALE) > 0)
            ->mapWithKeys(fn (array $item): array => [$item['nc']->id => self::formatCreditNoteLabel($item['nc'])])
            ->all();
    }

    private static function formatCreditNoteLabel(CreditNote $creditNote): string
    {
        $formattedBalance = number_format((float) $creditNote->getAvailableCollectionBalance(), 2, '.', '');

        return "{$creditNote->code} — {$creditNote->customer_name} (disponible: {$formattedBalance})";
    }

    /**
     * @param  array<int, array<string, mixed>>  $allocationItems
     * @return array<int, string>
     */
    private static function resolveInvoiceOptions(mixed $businessPartnerId, array $allocationItems, mixed $currentInvoiceId): array
    {
        if (blank($businessPartnerId)) {
            return [];
        }

        $selectedInvoiceIds = collect($allocationItems)
            ->pluck('invoice_id')
            ->filter(fn ($invoiceId): bool => filled($invoiceId))
            ->reject(fn ($invoiceId): bool => (string) $invoiceId === (string) $currentInvoiceId)
            ->map(fn ($invoiceId): int => (int) $invoiceId)
            ->values()
            ->all();

        return Invoice::query()
            ->select(['id', 'code', 'customer_name', 'total', 'paid_amount'])
            ->where('status', InvoiceStatusEnum::Issued)
            ->where('business_partner_id', (int) $businessPartnerId)
            ->whereRaw('total > paid_amount')
            ->when(
                $selectedInvoiceIds !== [],
                fn ($query) => $query->whereNotIn('id', $selectedInvoiceIds),
            )
            ->orderBy('code')
            ->limit(25)
            ->get()
            ->mapWithKeys(fn (Invoice $invoice): array => [
                $invoice->id => "{$invoice->code} — {$invoice->customer_name} (pendiente: ".CollectionAllocationMath::pending($invoice->total, $invoice->paid_amount).')',
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $allocationItems
     */
    private static function resolveRemainingCollectionAmount(mixed $collectionAmount, array $allocationItems, mixed $currentItemAmount): string
    {
        $normalizedCollectionAmount = CollectionAllocationMath::normalize($collectionAmount ?? 0);
        $normalizedCurrentItemAmount = CollectionAllocationMath::normalize($currentItemAmount ?? 0);

        $allocatedAmount = collect($allocationItems)
            ->pluck('amount')
            ->filter(fn ($amount): bool => filled($amount))
            ->reduce(
                fn (string $carry, $amount): string => bcadd(
                    $carry,
                    CollectionAllocationMath::normalize($amount),
                    CollectionAllocationMath::SCALE,
                ),
                '0.0000',
            );

        $allocatedByOtherItems = bcsub(
            $allocatedAmount,
            $normalizedCurrentItemAmount,
            CollectionAllocationMath::SCALE,
        );

        return CollectionAllocationMath::pending($normalizedCollectionAmount, $allocatedByOtherItems);
    }
}
