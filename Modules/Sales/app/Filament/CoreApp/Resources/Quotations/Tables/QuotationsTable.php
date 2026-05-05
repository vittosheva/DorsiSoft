<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Quotations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Core\Support\Actions\GeneratePdfBulkAction;
use Modules\Core\Support\Actions\SendDocumentEmailAction;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\CustomerNameTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\Core\Support\Tables\Filters\StatusFilter;
use Modules\Sales\Enums\QuotationStatusEnum;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Support\Tables\Filters\CustomerFilter;
use Modules\Sales\Support\Tables\Filters\SellerFilter;

final class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Sales quotations prepared for potential customers of this company. Quotations detail proposed products, quantities, and prices with an optional expiration date. An accepted quotation can be converted into a sales order or directly into an invoice.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->date('d/m/Y')
                    ->sortable(),

                CustomerNameTextColumn::make('customer_name'),

                TextColumn::make('seller.name')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge(),

                MoneyTextColumn::make('total')
                    ->currencyCode(fn(?Quotation $record): string => $record?->currency_code ?? ''),

                TextColumn::make('expires_at')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn(?Quotation $record) => $record?->expires_at?->isPast() && $record?->status === QuotationStatusEnum::Draft ? 'danger' : null),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                CustomerFilter::make('customer'),
                DateRangeFilter::make('issue_date'),
                StatusFilter::make('status')
                    ->options(QuotationStatusEnum::class),
                SellerFilter::make('seller'),
            ])
            ->recordActions([
                ViewAction::make()->modal(),
                EditAction::make()
                    ->visible(fn(?Quotation $record) => $record->status === QuotationStatusEnum::Draft),
                GeneratePdfAction::make(),
                SendDocumentEmailAction::make(),
                DeleteAction::make()
                    ->visible(fn(?Quotation $record) => $record->status === QuotationStatusEnum::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    GeneratePdfBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
