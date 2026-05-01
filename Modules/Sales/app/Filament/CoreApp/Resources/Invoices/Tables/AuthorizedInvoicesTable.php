<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\CustomerNameTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Inventory\Enums\ProductTypeEnum;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Support\Tables\Columns\CommercialStatusColumn;
use Modules\Sri\Support\Tables\Columns\ElectronicStatusColumn;

final class AuthorizedInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('A report of all sales invoices issued by the company.'))
            ->modifyQueryUsing(function ($query) use ($table): void {
                $query->where('electronic_status', ElectronicStatusEnum::Authorized);

                $businessPartnerId = $table->getArguments()['business_partner_id'] ?? null;
                if (filled($businessPartnerId)) {
                    $query->where('business_partner_id', $businessPartnerId);
                }

                if ($table->getArguments()['only_with_product_items'] ?? false) {
                    $query->whereHas('items', fn ($q) => $q->whereHas(
                        'product',
                        fn ($p) => $p->whereIn('type', [ProductTypeEnum::Product, ProductTypeEnum::Kit])
                    ));
                }
            })
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->date('d/m/Y')
                    ->sortable(),

                CustomerNameTextColumn::make('customer_name'),

                TextColumn::make('seller_name')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('salesOrder.code')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                CommercialStatusColumn::make(),

                ElectronicStatusColumn::make(),

                MoneyTextColumn::make('total')
                    ->currencyCode(fn ($record): string => $record->currency_code),

                MoneyTextColumn::make('paid_amount')
                    ->label(__('Paid'))
                    ->currencyCode(fn ($record): string => $record->currency_code)
                    ->color(fn ($record) => match ($record->paymentStatus()) {
                        'paid' => 'success',
                        'partially_paid' => 'warning',
                        default => 'gray',
                    }),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->defaultSort('created_at', 'desc');
    }
}
