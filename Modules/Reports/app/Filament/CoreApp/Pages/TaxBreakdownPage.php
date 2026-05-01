<?php

declare(strict_types=1);

namespace Modules\Reports\Filament\CoreApp\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\InvoiceItemTax;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use UnitEnum;

final class TaxBreakdownPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = 4;

    protected string $view = 'reports::pages.tax-breakdown';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('Tax Breakdown (SRI)');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Tax Breakdown Report (SRI)');
    }

    public function table(Table $table): Table
    {
        $companyId = filament()->getTenant()?->id;

        return $table
            ->description(__('A report breakdown of taxes applied to sales invoices, including tax type, name, rate, and totals.'))
            ->query(
                fn (): Builder => InvoiceItemTax::query()
                    ->select([
                        DB::raw('MIN(sales_invoice_item_taxes.id) as id'),
                        'sales_invoice_item_taxes.tax_type',
                        'sales_invoice_item_taxes.tax_name',
                        'sales_invoice_item_taxes.tax_rate',
                        DB::raw('COUNT(DISTINCT sales_invoices.id) as invoice_count'),
                        DB::raw('SUM(sales_invoice_item_taxes.base_amount) as base_total'),
                        DB::raw('SUM(sales_invoice_item_taxes.tax_amount) as tax_total'),
                    ])
                    ->join('sales_invoice_items', 'sales_invoice_item_taxes.invoice_item_id', '=', 'sales_invoice_items.id')
                    ->join('sales_invoices', 'sales_invoice_items.invoice_id', '=', 'sales_invoices.id')
                    ->where('sales_invoices.company_id', $companyId)
                    ->whereIn('sales_invoices.status', [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value])
                    ->whereNull('sales_invoices.deleted_at')
                    ->groupBy('sales_invoice_item_taxes.tax_type', 'sales_invoice_item_taxes.tax_name', 'sales_invoice_item_taxes.tax_rate')
                    ->orderBy('sales_invoice_item_taxes.tax_type')
            )
            ->columns([
                TextColumn::make('tax_type')
                    ->badge()
                    ->sortable(['sales_invoice_item_taxes.tax_type']),

                TextColumn::make('tax_name')
                    ->searchable(['sales_invoice_item_taxes.tax_name']),

                TextColumn::make('tax_rate')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->alignRight(),

                TextColumn::make('invoice_count')
                    ->numeric()
                    ->alignRight()
                    ->summarize(Sum::make()),

                MoneyTextColumn::make('base_total')
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD'),

                MoneyTextColumn::make('tax_total')
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD'),
            ])
            ->filters([
                DateRangeFilter::make('sales_invoices.issue_date'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label(__('Export'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->exports([
                        ExcelExport::make('table')
                            ->fromTable()
                            ->withFilename(fn () => 'impuestos-sri-'.now()->format('Y-m-d'))
                            ->withColumns([
                                Column::make('tax_type')->heading(__('Type')),
                                Column::make('tax_name')->heading(__('Tax')),
                                Column::make('tax_rate')->heading(__('Rate %')),
                                Column::make('invoice_count')->heading(__('Invoices')),
                                Column::make('base_total')->heading(__('Tax Base')),
                                Column::make('tax_total')->heading(__('Tax Amount')),
                            ]),
                    ]),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()->fromTable(),
                    ]),
            ])
            ->paginated(false)
            ->defaultSort('base_total', 'desc')
            ->defaultKeySort(false);
    }
}
