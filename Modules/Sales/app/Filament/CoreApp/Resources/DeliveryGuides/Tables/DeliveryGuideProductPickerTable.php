<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Sales\Models\InvoiceItem;

final class DeliveryGuideProductPickerTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Select the products to include in the delivery guide. Only products that are part of the invoice items will be shown.'))
            ->modifyQueryUsing(function ($query) use ($table): void {
                $productIds = $table->getArguments()['product_ids'] ?? null;
                if (filled($productIds)) {
                    $query->whereIn('id', $productIds);
                }

                $invoiceId = $table->getArguments()['invoice_id'] ?? null;
                if (filled($invoiceId)) {
                    $productIdsToInclude = InvoiceItem::query()
                        ->where('invoice_id', $invoiceId)
                        ->whereNotNull('product_id')
                        ->pluck('product_id')
                        ->toArray();
                    $query->whereIn('id', $productIdsToInclude);
                }
            })
            ->columns([
                CodeTextColumn::make('code'),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('unit.name')->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
