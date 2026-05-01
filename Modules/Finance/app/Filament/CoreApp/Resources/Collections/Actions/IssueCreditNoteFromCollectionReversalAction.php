<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Collections\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\IssueCreditNoteService;

final class IssueCreditNoteFromCollectionReversalAction
{
    public static function make(): Action
    {
        return Action::make('issueCreditNoteFromReversal')
            ->label(__('Issue Credit Note'))
            ->icon(Heroicon::OutlinedDocumentMinus)
            ->color('warning')
            ->tooltip(__('Issue a formal credit note with allocation reversal'))
            ->fillForm(function (CollectionAllocation $record): array {
                $invoice = Invoice::withoutGlobalScopes()
                    ->with(['items' => fn ($q) => $q->orderBy('sort_order')])
                    ->find($record->invoice_id);

                return [
                    'items' => $invoice
                        ? $invoice->items->map(fn ($item) => [
                            'product_id' => $item->product_id,
                            'product_code' => $item->product_code,
                            'product_name' => $item->product_name,
                            'product_unit' => $item->product_unit,
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'discount_type' => $item->discount_type?->value,
                            'discount_value' => $item->discount_value,
                            'discount_amount' => $item->discount_amount,
                            'subtotal' => $item->subtotal,
                            'tax_amount' => $item->tax_amount,
                            'total' => $item->total,
                            'sort_order' => $item->sort_order,
                        ])->all()
                        : [],
                ];
            })
            ->schema([
                Repeater::make('items')
                    ->label(__('Items to Return'))
                    ->schema([
                        TextInput::make('product_code')
                            ->label(__('Code'))
                            ->readOnly()
                            ->columnSpan(1),

                        TextInput::make('product_name')
                            ->label(__('Product'))
                            ->readOnly()
                            ->columnSpan(3),

                        TextInput::make('quantity')
                            ->label(__('Qty'))
                            ->numeric()
                            ->step(0.000001)
                            ->required()
                            ->minValue(0.000001)
                            ->columnSpan(1),

                        TextInput::make('unit_price')
                            ->readOnly()
                            ->prefix(fn (CollectionAllocation $record): string => MoneyTextInput::symbolForCode($record->collection?->currency_code ?? 'USD'))
                            ->columnSpan(1),

                        TextInput::make('subtotal')
                            ->readOnly()
                            ->prefix(fn (CollectionAllocation $record): string => MoneyTextInput::symbolForCode($record->collection?->currency_code ?? 'USD'))
                            ->columnSpan(1),

                        TextInput::make('total')
                            ->readOnly()
                            ->prefix(fn (CollectionAllocation $record): string => MoneyTextInput::symbolForCode($record->collection?->currency_code ?? 'USD'))
                            ->columnSpan(1),
                    ])
                    ->columns(8)
                    ->deletable(true)
                    ->addable(false)
                    ->reorderable(false)
                    ->columnSpanFull(),

                Textarea::make('reason')
                    ->label(__('Reason'))
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),

                Select::make('credit_disposition')
                    ->label(__('Credit Disposition'))
                    ->options([
                        'floating_balance' => __('Keep as floating credit balance'),
                        'cash_refunded' => __('Refund in cash to customer'),
                    ])
                    ->required()
                    ->default('floating_balance')
                    ->columnSpanFull(),
            ])
            ->action(function (CollectionAllocation $record, array $data): void {
                $items = array_map(function (array $item): array {
                    return [
                        'product_id' => $item['product_id'] ?? null,
                        'product_code' => $item['product_code'] ?? null,
                        'product_name' => $item['product_name'] ?? null,
                        'product_unit' => $item['product_unit'] ?? null,
                        'description' => $item['description'] ?? null,
                        'quantity' => CollectionAllocationMath::normalize($item['quantity'] ?? 0),
                        'unit_price' => CollectionAllocationMath::normalize($item['unit_price'] ?? 0),
                        'discount_type' => $item['discount_type'] ?? null,
                        'discount_value' => $item['discount_value'] ?? null,
                        'discount_amount' => CollectionAllocationMath::normalize($item['discount_amount'] ?? 0),
                        'subtotal' => CollectionAllocationMath::normalize($item['subtotal'] ?? 0),
                        'tax_amount' => CollectionAllocationMath::normalize($item['tax_amount'] ?? 0),
                        'total' => CollectionAllocationMath::normalize($item['total'] ?? 0),
                        'sort_order' => $item['sort_order'] ?? 0,
                    ];
                }, $data['items'] ?? []);

                try {
                    /** @var IssueCreditNoteService $service */
                    $service = app(IssueCreditNoteService::class);
                    $service->fromCollectionReversal(
                        allocation: $record,
                        items: $items,
                        reason: (string) ($data['reason'] ?? ''),
                        creditDisposition: (string) ($data['credit_disposition'] ?? 'floating_balance'),
                        issuedBy: Auth::id(),
                    );

                    Notification::make()
                        ->title(__('Credit note issued'))
                        ->success()
                        ->send();
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['reason' => $e->getMessage()]);
                }
            })
            ->visible(fn (CollectionAllocation $record): bool => ! $record->collection?->isVoided());
    }
}
