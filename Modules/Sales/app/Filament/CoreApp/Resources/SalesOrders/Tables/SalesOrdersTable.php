<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Core\Support\Actions\GeneratePdfBulkAction;
use Modules\Core\Support\Actions\SendDocumentEmailAction;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\CustomerNameTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Filters\StatusFilter;
use Modules\Sales\Enums\SalesOrderStatusEnum;
use Modules\Sales\Support\Tables\Filters\CustomerFilter;
use Modules\Sales\Support\Tables\Filters\SellerFilter;
use Modules\Workflow\Support\Tables\Columns\ApprovalDecisionTextColumn;

final class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Sales orders generated for this company, representing confirmed purchase commitments from customers. Sales orders are typically created from approved quotations and serve as the basis for invoice generation. Each order tracks its fulfillment status and the originating quotation.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->date('d/m/Y')
                    ->sortable(),

                CustomerNameTextColumn::make('customer_name'),

                TextColumn::make('seller_name')
                    ->placeholder('—'),

                TextColumn::make('quotation.code')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge(),

                ApprovalDecisionTextColumn::forFlow('confirmation_approval', 'sales_order_confirmation'),

                MoneyTextColumn::make('total')
                    ->currencyCode(fn ($record): string => $record->currency_code),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                CustomerFilter::make('customer'),
                DateRangeFilter::make('issue_date'),
                StatusFilter::make('status')
                    ->options(SalesOrderStatusEnum::class),
                SellerFilter::make('seller'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->status === SalesOrderStatusEnum::Pending),
                SendDocumentEmailAction::make(),
                GeneratePdfAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->status === SalesOrderStatusEnum::Pending),
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
