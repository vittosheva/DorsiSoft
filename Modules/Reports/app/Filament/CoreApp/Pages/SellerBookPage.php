<?php

declare(strict_types=1);

namespace Modules\Reports\Filament\CoreApp\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CustomerNameTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\Core\Support\Tables\Filters\StatusFilter;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Support\Tables\Filters\CustomerFilter;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use UnitEnum;

final class SellerBookPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;

    protected string $view = 'reports::pages.libro-ventas';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('Sales Book');
    }

    public function table(Table $table): Table
    {
        $companyId = filament()->getTenant()?->id;

        return $table
            ->description(__('A report of all sales invoices issued by the company.'))
            ->query(
                fn (): Builder => Invoice::query()
                    ->with(['items.taxes'])
                    ->where('company_id', $companyId)
                    ->whereIn('status', [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value])
            )
            ->columns([
                CodeTextColumn::make('code'),

                TextColumn::make('issue_date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->date('d/m/Y')
                    ->sortable(),

                CustomerNameTextColumn::make('customer_name')
                    ->limit(30)
                    ->tooltip(fn (Model $record) => $record->customer_name),

                TextColumn::make('customer_identification')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('seller_name')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                MoneyTextColumn::make('subtotal')
                    ->currencyCode(fn (Model $record): string => $record->currency_code),

                MoneyTextColumn::make('discount_amount')
                    ->currencyCode(fn (Model $record): string => $record->currency_code),

                MoneyTextColumn::make('tax_base')
                    ->currencyCode(fn (Model $record): string => $record->currency_code),

                MoneyTextColumn::make('tax_amount')
                    ->currencyCode(fn (Model $record): string => $record->currency_code),

                MoneyTextColumn::make('total')
                    ->currencyCode(fn (Model $record): string => $record->currency_code)
                    ->weight('bold'),

                MoneyTextColumn::make('paid_amount')
                    ->currencyCode(fn (Model $record): string => $record->currency_code),

                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                CustomerFilter::make('business_partner_id')
                    ->multiple()
                    ->columnSpan(3),

                DateRangeFilter::make('issue_date'),

                StatusFilter::make('status')
                    ->options(InvoiceStatusEnum::class)
                    ->multiple(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label(__('Export'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->exports([
                        ExcelExport::make('table')
                            ->fromTable()
                            ->withFilename(fn () => 'libro-ventas-'.now()->format('Y-m-d'))
                            ->withColumns([
                                Column::make('code')->heading(__('Code')),
                                Column::make('issue_date')->heading(__('Issue date')),
                                Column::make('due_date')->heading(__('Due date')),
                                Column::make('customer_name')->heading(__('Customer')),
                                Column::make('customer_identification')->heading(__('Tax ID')),
                                Column::make('seller_name')->heading(__('Seller')),
                                Column::make('subtotal')->heading(__('Subtotal')),
                                Column::make('discount_amount')->heading(__('Discount')),
                                Column::make('tax_base')->heading(__('Tax base')),
                                Column::make('tax_amount')->heading(__('VAT')),
                                Column::make('total')->heading(__('Total')),
                                Column::make('paid_amount')->heading(__('Paid')),
                            ]),
                    ]),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()->fromTable(),
                    ]),
            ])
            ->defaultSort('issue_date', 'desc');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Sales Book');
    }
}
