<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Collections\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Finance\Filament\CoreApp\Resources\Collections\Actions\IssueCreditNoteFromCollectionReversalAction;
use Modules\Finance\Models\Collection;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Services\AllocateCollectionToInvoiceService;
use Modules\Finance\Services\ReverseCollectionAllocationService;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;

final class CollectionAllocationsRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'allocations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Invoices Allocated');
    }

    public function form(Schema $schema): Schema
    {
        /** @var Collection $collection */
        $collection = $this->getOwnerRecord();

        return $schema
            ->components([
                Select::make('invoice_id')
                    ->options(fn (): array => $this->resolveInvoiceOptions())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->getSearchResultsUsing(
                        fn (?string $search): array => $this->resolveInvoiceOptions($search)
                    )
                    ->afterStateUpdated(function ($state, Set $set) use ($collection): void {
                        if (blank($state)) {
                            $set('amount', $this->resolveRemainingCollectionAmount($collection));

                            return;
                        }

                        $invoice = Invoice::query()->select(['id', 'total', 'paid_amount'])->find($state);

                        if (! $invoice) {
                            return;
                        }

                        $set('amount', $this->resolveSuggestedAllocationAmount($collection, $invoice));
                    })
                    ->getOptionLabelUsing(fn ($value) => optional(Invoice::query()->select(['id', 'code'])->find($value))?->code)
                    ->columnSpanFull(),

                MoneyTextInput::make('amount')
                    ->currencyCode(fn (): string => $this->getOwnerRecord()->currency_code ?? 'USD')
                    ->required()
                    ->minValue(0.0001)
                    ->suffixAction(
                        Action::make('set_invoice_pending_amount')
                            ->label(__('Apply maximum'))
                            ->icon(Heroicon::Bolt)
                            ->action(function (Get $get, Set $set) use ($collection): void {
                                $invoiceId = $get('invoice_id');

                                if (blank($invoiceId)) {
                                    return;
                                }

                                $invoice = Invoice::query()->select(['id', 'total', 'paid_amount'])->find($invoiceId);

                                if (! $invoice) {
                                    return;
                                }

                                $set('amount', $this->resolveSuggestedAllocationAmount($collection, $invoice));
                            })
                    )
                    ->hintAction(
                        Action::make('set_invoice_full_amount')
                            ->label(__('Pay full invoice'))
                            ->icon(Heroicon::CurrencyDollar)
                            ->requiresConfirmation(function (Get $get) use ($collection): bool {
                                $invoiceId = $get('invoice_id');

                                if (blank($invoiceId)) {
                                    return false;
                                }

                                $invoice = Invoice::query()->select(['id', 'total', 'paid_amount'])->find($invoiceId);

                                if (! $invoice) {
                                    return false;
                                }

                                $pendingInvoiceAmount = $this->resolvePendingInvoiceAmount($invoice);
                                $remainingCollectionAmount = $this->resolveRemainingCollectionAmount($collection);

                                return bccomp($pendingInvoiceAmount, $remainingCollectionAmount, CollectionAllocationMath::SCALE) > 0;
                            })
                            ->modalHeading(__('Adjust Collection Amount'))
                            ->modalDescription(__('The collection balance is insufficient. By confirming, the collection amount will be increased to cover the total pending invoice amount.'))
                            ->action(function (Get $get, Set $set) use ($collection): void {
                                $invoiceId = $get('invoice_id');

                                if (blank($invoiceId)) {
                                    return;
                                }

                                $invoice = Invoice::query()->select(['id', 'total', 'paid_amount'])->find($invoiceId);

                                if (! $invoice) {
                                    return;
                                }

                                $pendingInvoiceAmount = $this->resolvePendingInvoiceAmount($invoice);
                                $remainingCollectionAmount = $this->resolveRemainingCollectionAmount($collection);

                                if (bccomp($pendingInvoiceAmount, $remainingCollectionAmount, CollectionAllocationMath::SCALE) > 0) {
                                    $additionalCollectionAmount = bcsub($pendingInvoiceAmount, $remainingCollectionAmount, CollectionAllocationMath::SCALE);
                                    $requiredCollectionAmount = bcadd(
                                        CollectionAllocationMath::normalize($collection->amount),
                                        $additionalCollectionAmount,
                                        CollectionAllocationMath::SCALE,
                                    );

                                    $collection->forceFill(['amount' => $requiredCollectionAmount])->save();
                                }

                                $set('amount', $pendingInvoiceAmount);
                            })
                    )
                    ->default(fn (): string => $this->resolveRemainingCollectionAmount($collection))
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Invoices allocated to this collection payment, showing how the received amount has been distributed across outstanding invoices. Each allocation reduces the balance of the matched invoice. The total allocated amount cannot exceed the collection\'s original amount.'))
            // ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('creator:id,name,avatar_url'))
            ->recordTitleAttribute('invoice.code')
            ->columns([
                TextColumn::make('invoice.code')
                    ->inverseRelationship('allocations')
                    ->label(__('Invoice'))
                    ->weight(FontWeight::Medium),

                TextColumn::make('invoice.issue_date')
                    ->inverseRelationship('allocations')
                    ->label(__('Invoice Date'))
                    ->date('d/m/Y'),

                TextColumn::make('invoice.customer_name')
                    ->inverseRelationship('allocations')
                    ->label(__('Customer')),

                MoneyTextColumn::make('invoice.total')
                    ->inverseRelationship('allocations')
                    ->label(__('Invoice Total'))
                    ->currencyCode(fn (): string => $this->getOwnerRecord()->currency_code ?? 'USD'),

                MoneyTextColumn::make('amount')
                    ->label(__('Allocated'))
                    ->currencyCode(fn (): string => $this->getOwnerRecord()->currency_code ?? 'USD'),

                TextColumn::make('allocated_at')
                    ->label(__('Date'))
                    ->date('d/m/Y'),

                // CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Allocate to Invoice'))
                    ->visible(fn (): bool => $this->hasAvailableInvoices())
                    ->using(function (array $data, string $model): Model {
                        /** @var Collection $collection */
                        $collection = $this->getOwnerRecord();

                        $invoiceId = (int) ($data['invoice_id'] ?? 0);

                        if ($collection->allocations()->where('invoice_id', $invoiceId)->exists()) {
                            throw ValidationException::withMessages([
                                'invoice_id' => __('This invoice is already allocated to this collection.'),
                            ]);
                        }

                        $remainingCollectionAmount = $this->resolveRemainingCollectionAmount($collection);

                        if (bccomp($remainingCollectionAmount, '0.0000', CollectionAllocationMath::SCALE) <= 0) {
                            throw ValidationException::withMessages([
                                'amount' => __('This collection has no remaining balance to allocate.'),
                            ]);
                        }

                        $invoice = Invoice::query()->findOrFail($invoiceId, ['id', 'business_partner_id', 'customer_name', 'total', 'paid_amount']);

                        if (filled($collection->business_partner_id)) {
                            if ((int) $invoice->business_partner_id !== (int) $collection->business_partner_id) {
                                throw ValidationException::withMessages([
                                    'invoice_id' => __('You can only allocate invoices from the same customer as the collection.'),
                                ]);
                            }
                        } elseif (filled($collection->customer_name)) {
                            if (mb_strtolower(mb_trim((string) $invoice->customer_name)) !== mb_strtolower(mb_trim((string) $collection->customer_name))) {
                                throw ValidationException::withMessages([
                                    'invoice_id' => __('You can only allocate invoices from the same customer as the collection.'),
                                ]);
                            }
                        }

                        $requestedAmount = CollectionAllocationMath::normalize($data['amount'] ?? 0);
                        $pendingInvoiceAmount = $this->resolvePendingInvoiceAmount($invoice);

                        if (CollectionAllocationMath::exceedsWithTolerance($requestedAmount, $remainingCollectionAmount)) {
                            throw ValidationException::withMessages([
                                'amount' => __('The allocated amount cannot exceed the remaining collection balance.'),
                            ]);
                        }

                        if (CollectionAllocationMath::exceedsWithTolerance($requestedAmount, $pendingInvoiceAmount)) {
                            throw ValidationException::withMessages([
                                'amount' => __('The allocated amount cannot exceed the pending invoice balance.'),
                            ]);
                        }

                        /** @var AllocateCollectionToInvoiceService $allocationService */
                        $allocationService = app(AllocateCollectionToInvoiceService::class);

                        return $allocationService->allocate(
                            collection: $collection,
                            invoiceId: $invoiceId,
                            amount: $requestedAmount,
                        );
                    }),
            ])
            ->recordActions([
                Action::make('reverseAllocation')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->tooltip(__('Reverse allocation'))
                    ->schema([
                        TextInput::make('reversed_amount')
                            ->numeric()
                            ->minValue(0.0001)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('reason')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->fillForm(function (CollectionAllocation $record): array {
                        return [
                            'reversed_amount' => $record->amount,
                        ];
                    })
                    ->action(function (CollectionAllocation $record, array $data): void {
                        /** @var ReverseCollectionAllocationService $service */
                        $service = app(ReverseCollectionAllocationService::class);

                        $service->reverse(
                            allocation: $record,
                            reversedAmount: number_format((float) ($data['reversed_amount'] ?? 0), 4, '.', ''),
                            reason: (string) ($data['reason'] ?? ''),
                            reversedBy: Auth::id(),
                        );

                        Notification::make()
                            ->title(__('Allocation reversed'))
                            ->body(__('The allocation has been reversed successfully.'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn (CollectionAllocation $record): bool => ! $record->collection?->isVoided())
                    ->requiresConfirmation()
                    ->slideOver(false),

                IssueCreditNoteFromCollectionReversalAction::make(),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<int, string>
     */
    private function resolveInvoiceOptions(?string $search = null): array
    {
        /** @var Collection $collection */
        $collection = $this->getOwnerRecord();

        if (blank($collection->business_partner_id) && blank($collection->customer_name)) {
            return [];
        }

        $allocatedInvoiceIds = $this->getOwnerRecord()
            ->allocations()
            ->pluck('invoice_id')
            ->all();

        return Invoice::query()
            ->select(['id', 'code', 'customer_name', 'total', 'paid_amount'])
            ->where('status', InvoiceStatusEnum::Issued)
            ->when(
                filled($collection->business_partner_id),
                fn ($query) => $query->where('business_partner_id', (int) $collection->business_partner_id),
                fn ($query) => $query->whereRaw('LOWER(customer_name) = ?', [mb_strtolower(mb_trim((string) $collection->customer_name))]),
            )
            ->whereRaw('total > paid_amount')
            ->when(
                $allocatedInvoiceIds !== [],
                fn ($query) => $query->whereNotIn('id', $allocatedInvoiceIds),
            )
            ->when(
                filled($search),
                fn ($query) => $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('code', 'like', mb_trim((string) $search).'%')
                        ->orWhere('customer_name', 'like', '%'.mb_trim((string) $search).'%');
                }),
            )
            ->orderBy('code')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn (Invoice $invoice): array => [
                $invoice->id => $invoice->code.' — '.$invoice->customer_name.' ('.__('pending').': '.$this->formatDecimalAmount($this->resolvePendingInvoiceAmount($invoice)).')',
            ])
            ->all();
    }

    private function hasAvailableInvoices(): bool
    {
        return $this->resolveInvoiceOptions() !== []
            && bccomp($this->resolveRemainingCollectionAmount($this->getOwnerRecord()), '0.0000', CollectionAllocationMath::SCALE) > 0;
    }

    private function resolveSuggestedAllocationAmount(Collection $collection, Invoice $invoice): string
    {
        $remainingCollectionAmount = $this->resolveRemainingCollectionAmount($collection);
        $pendingInvoiceAmount = $this->resolvePendingInvoiceAmount($invoice);

        if (bccomp($remainingCollectionAmount, $pendingInvoiceAmount, CollectionAllocationMath::SCALE) <= 0) {
            return $remainingCollectionAmount;
        }

        return $pendingInvoiceAmount;
    }

    private function resolveRemainingCollectionAmount(Collection $collection): string
    {
        $allocatedAmount = CollectionAllocationMath::normalize($collection->allocations()->sum('amount'));

        return CollectionAllocationMath::pending($collection->amount, $allocatedAmount);
    }

    private function resolvePendingInvoiceAmount(Invoice $invoice): string
    {
        return CollectionAllocationMath::pending($invoice->total, $invoice->paid_amount);
    }

    private function formatDecimalAmount(string $amount): string
    {
        return number_format((float) $amount, CollectionAllocationMath::SCALE, '.', '');
    }
}
