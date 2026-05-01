<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\IssueCreditNoteService;

final class IssueCreditNoteFromInvoiceAction
{
    public static function make(): Action
    {
        return Action::make('issueCreditNote')
            ->label(__('Issue Credit Note'))
            ->icon(Heroicon::OutlinedDocumentMinus)
            ->color('warning')
            ->visible(function (Invoice $record): bool {
                return in_array($record->status, [InvoiceStatusEnum::Issued, InvoiceStatusEnum::Paid], true);
            })
            ->fillForm(function (Invoice $record): array {
                return [
                    'items' => $record->items()
                        ->orderBy('sort_order')
                        ->get()
                        ->map(fn ($item) => [
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
                        ])
                        ->all(),
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
                            ->prefix(fn (Invoice $record): string => MoneyTextInput::symbolForCode($record->currency_code))
                            ->columnSpan(1),

                        TextInput::make('subtotal')
                            ->readOnly()
                            ->prefix(fn (Invoice $record): string => MoneyTextInput::symbolForCode($record->currency_code))
                            ->columnSpan(1),

                        TextInput::make('total')
                            ->readOnly()
                            ->prefix(fn (Invoice $record): string => MoneyTextInput::symbolForCode($record->currency_code))
                            ->columnSpan(1),
                    ])
                    ->columns(8)
                    ->deletable(true)
                    ->addable(false)
                    ->reorderable(false)
                    ->columnSpanFull(),

                Textarea::make('reason')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
            ])
            ->action(function (Invoice $record, array $data, Action $action): void {
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
                    $service->fromInvoice(
                        invoice: $record,
                        items: $items,
                        reason: (string) ($data['reason'] ?? ''),
                        issuedBy: Auth::id(),
                    );

                    Notification::make()
                        ->title(__('Credit note issued'))
                        ->success()
                        ->send();
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['reason' => $e->getMessage()]);
                }
            });
    }
}
