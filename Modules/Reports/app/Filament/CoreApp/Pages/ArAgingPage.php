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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\Tables\Columns\CustomerNameTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Support\Tables\Filters\CustomerFilter;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use UnitEnum;

final class ArAgingPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 2;

    protected string $view = 'reports::pages.ar-aging';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('Accounts Receivable');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Accounts Receivable Aging');
    }

    public function table(Table $table): Table
    {
        $companyId = filament()->getTenant()?->id;

        return $table
            ->description(__('A report of all issued sales invoices grouped by customer, showing the aging of pending amounts.'))
            ->query(
                fn (): Builder => Invoice::query()
                    ->select([
                        DB::raw('MIN(sales_invoices.id) as id'),
                        'sales_invoices.business_partner_id',
                        'sales_invoices.customer_name',
                        'sales_invoices.customer_identification',
                        DB::raw('COUNT(*) as invoice_count'),
                        DB::raw('SUM(sales_invoices.total - sales_invoices.paid_amount - sales_invoices.credited_amount) as total_pending'),
                        DB::raw('SUM(CASE WHEN (NOW()::date - COALESCE(sales_invoices.due_date, sales_invoices.issue_date)) <= 0 THEN sales_invoices.total - sales_invoices.paid_amount - sales_invoices.credited_amount ELSE 0 END) as band_current'),
                        DB::raw('SUM(CASE WHEN (NOW()::date - COALESCE(sales_invoices.due_date, sales_invoices.issue_date)) BETWEEN 1 AND 30 THEN sales_invoices.total - sales_invoices.paid_amount - sales_invoices.credited_amount ELSE 0 END) as band_30'),
                        DB::raw('SUM(CASE WHEN (NOW()::date - COALESCE(sales_invoices.due_date, sales_invoices.issue_date)) BETWEEN 31 AND 60 THEN sales_invoices.total - sales_invoices.paid_amount - sales_invoices.credited_amount ELSE 0 END) as band_60'),
                        DB::raw('SUM(CASE WHEN (NOW()::date - COALESCE(sales_invoices.due_date, sales_invoices.issue_date)) BETWEEN 61 AND 90 THEN sales_invoices.total - sales_invoices.paid_amount - sales_invoices.credited_amount ELSE 0 END) as band_90'),
                        DB::raw('SUM(CASE WHEN (NOW()::date - COALESCE(sales_invoices.due_date, sales_invoices.issue_date)) > 90 THEN sales_invoices.total - sales_invoices.paid_amount - sales_invoices.credited_amount ELSE 0 END) as band_90plus'),
                    ])
                    ->where('sales_invoices.company_id', $companyId)
                    ->where('sales_invoices.status', InvoiceStatusEnum::Issued->value)
                    ->groupBy('sales_invoices.business_partner_id', 'sales_invoices.customer_name', 'sales_invoices.customer_identification')
            )
            ->columns([
                CustomerNameTextColumn::make('customer_name')
                    ->searchable(['sales_invoices.customer_name'])
                    ->sortable(['sales_invoices.customer_name'])
                    ->limit(30)
                    ->tooltip(fn (Model $record) => $record->customer_name),

                TextColumn::make('customer_identification')
                    ->searchable(['sales_invoices.customer_identification']),

                TextColumn::make('invoice_count')
                    ->numeric()
                    ->alignRight()
                    ->summarize(Sum::make()),

                MoneyTextColumn::make('band_current')
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->color('success'),

                MoneyTextColumn::make('band_30')
                    ->label('1-30 '.__('days'))
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->color('warning'),

                MoneyTextColumn::make('band_60')
                    ->label('31-60 '.__('days'))
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->color('warning'),

                MoneyTextColumn::make('band_90')
                    ->label('61-90 '.__('days'))
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->color('danger'),

                MoneyTextColumn::make('band_90plus')
                    ->label('>90 '.__('days'))
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->color('danger')
                    ->weight('bold'),

                MoneyTextColumn::make('total_pending')
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->weight('bold'),
            ])
            ->filters([
                CustomerFilter::make('business_partner_id')
                    ->multiple()
                    ->columnSpan(3),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label(__('Export'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->exports([
                        ExcelExport::make('table')
                            ->fromTable()
                            ->withFilename(fn () => 'ar-aging-'.now()->format('Y-m-d'))
                            ->withColumns([
                                Column::make('customer_name')->heading(__('Customer')),
                                Column::make('customer_identification')->heading(__('Tax ID')),
                                Column::make('invoice_count')->heading(__('Invoices')),
                                Column::make('band_current')->heading(__('Current')),
                                Column::make('band_30')->heading('1-30 '.__('days')),
                                Column::make('band_60')->heading('31-60 '.__('days')),
                                Column::make('band_90')->heading('61-90 '.__('days')),
                                Column::make('band_90plus')->heading('>90 '.__('days')),
                                Column::make('total_pending')->heading(__('Total Pending')),
                            ]),
                    ]),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()->fromTable(),
                    ]),
            ])
            ->defaultSort('total_pending', 'desc')
            ->defaultKeySort(false);
    }
}
