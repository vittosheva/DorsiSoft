<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Withholdings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Columns\SupplierNameTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\Core\Support\Tables\Filters\StatusFilter;
use Modules\Sri\Support\Tables\Columns\CommercialStatusColumn;
use Modules\Sri\Support\Tables\Columns\ElectronicStatusColumn;

final class WithholdingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Tax withholding vouchers issued by this company as a withholding agent. Withholdings are applied to supplier invoices and purchase settlements and are submitted electronically to the SRI. Each withholding records the tax base, applicable rates, and the retained amounts for income tax and VAT.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->date('d/m/Y')
                    ->sortable(),

                SupplierNameTextColumn::make('supplier_name'),

                TextColumn::make('source_document_number')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                MoneyTextColumn::make('items_sum_withheld_amount')
                    ->label(__('Withheld amount'))
                    ->currencyCode(fn ($record): string => $record->company?->defaultCurrency?->code ?? '')
                    ->placeholder('—'),

                TextColumn::make('period_fiscal')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
