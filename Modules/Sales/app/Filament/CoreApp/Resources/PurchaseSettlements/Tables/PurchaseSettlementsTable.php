<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Core\Support\Actions\SendDocumentEmailAction;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Columns\SupplierNameTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\Core\Support\Tables\Filters\StatusFilter;
use Modules\Sales\Enums\PurchaseSettlementStatusEnum;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sri\Support\Tables\Columns\CommercialStatusColumn;
use Modules\Sri\Support\Tables\Columns\ElectronicStatusColumn;

final class PurchaseSettlementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Purchase settlement documents issued by this company when acquiring goods or services from natural persons not required to issue invoices. Purchase settlements are SRI-compliant documents that substitute the supplier\'s invoice and must be electronically authorized. They are commonly used for agricultural purchases and services from unregistered providers.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->date('d/m/Y')
                    ->sortable(),

                SupplierNameTextColumn::make('supplier_name'),

                MoneyTextColumn::make('total')
                    ->currencyCode(fn ($record): string => $record->currency_code ?? 'USD'),

                CommercialStatusColumn::make(),

                ElectronicStatusColumn::make(),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                StatusFilter::make(),
                DateRangeFilter::make('issue_date'),
            ])
            ->recordActions([
                ViewAction::make()->modal(),
                EditAction::make()->visible(fn (?PurchaseSettlement $record) => $record->isElectronicDocumentMutable()),
                SendDocumentEmailAction::make(),
                GeneratePdfAction::make(),
                DeleteAction::make()
                    ->visible(fn (?PurchaseSettlement $record) => $record->status === PurchaseSettlementStatusEnum::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
