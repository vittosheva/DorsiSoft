<?php

declare(strict_types=1);

namespace Modules\Reports\Filament\CoreApp\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Average;
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
use Modules\Sales\Models\Invoice;
use Modules\Sales\Support\Tables\Filters\SellerFilter;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use UnitEnum;

final class SellerPerformancePage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 3;

    protected string $view = 'reports::pages.seller-performance';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('Seller Performance');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Seller Performance Report');
    }

    public function table(Table $table): Table
    {
        $companyId = filament()->getTenant()?->id;

        return $table
            ->description(__('A report of all issued sales invoices grouped by seller, showing key performance indicators such as total revenue, collected amount, pending amount, average ticket, and collection rate.'))
            ->query(
                fn (): Builder => Invoice::query()
                    ->select([
                        DB::raw('MIN(sales_invoices.id) as id'),
                        'sales_invoices.seller_id',
                        'sales_invoices.seller_name',
                        DB::raw('COUNT(*) as invoice_count'),
                        DB::raw('SUM(sales_invoices.total) as revenue'),
                        DB::raw('SUM(sales_invoices.paid_amount) as collected'),
                        DB::raw('SUM(sales_invoices.total - sales_invoices.paid_amount - sales_invoices.credited_amount) as pending'),
                        DB::raw('AVG(sales_invoices.total) as avg_ticket'),
                        DB::raw('ROUND(SUM(sales_invoices.paid_amount) / NULLIF(SUM(sales_invoices.total), 0) * 100, 1) as collection_rate'),
                    ])
                    ->where('sales_invoices.company_id', $companyId)
                    ->whereIn('sales_invoices.status', [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value])
                    ->whereNotNull('sales_invoices.seller_name')
                    ->groupBy('sales_invoices.seller_id', 'sales_invoices.seller_name')
            )
            ->columns([
                TextColumn::make('seller_name')
                    ->searchable(['sales_invoices.seller_name'])
                    ->sortable(['sales_invoices.seller_name']),

                TextColumn::make('invoice_count')
                    ->label(__('Invoices'))
                    ->numeric()
                    ->alignRight()
                    ->sortable(),

                MoneyTextColumn::make('revenue')
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->sortable(),

                MoneyTextColumn::make('collected')
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD'),

                MoneyTextColumn::make('pending')
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->color(fn ($record) => (float) $record->pending > 0 ? 'warning' : 'success'),

                MoneyTextColumn::make('avg_ticket')
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD'),

                TextColumn::make('collection_rate')
                    ->label(__('Collection %'))
                    ->suffix('%')
                    ->alignRight()
                    ->color(fn ($record) => match (true) {
                        (float) $record->collection_rate >= 80 => 'success',
                        (float) $record->collection_rate >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->summarize(Average::make()->suffix('%')),
            ])
            ->filters([
                SellerFilter::make('seller_id')
                    ->multiple()
                    ->columnSpan(3),

                DateRangeFilter::make('issue_date'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label(__('Export'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->exports([
                        ExcelExport::make('table')
                            ->fromTable()
                            ->withFilename(fn () => 'seller-performance-'.now()->format('Y-m-d'))
                            ->withColumns([
                                Column::make('seller_name')->heading(__('Seller')),
                                Column::make('invoice_count')->heading(__('Invoices')),
                                Column::make('revenue')->heading(__('Revenue')),
                                Column::make('collected')->heading(__('Collected')),
                                Column::make('pending')->heading(__('Pending')),
                                Column::make('avg_ticket')->heading(__('Avg Ticket')),
                                Column::make('collection_rate')->heading(__('Collection %')),
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
            ->defaultSort('revenue', 'desc')
            ->defaultKeySort(false);
    }
}
