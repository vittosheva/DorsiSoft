<?php

declare(strict_types=1);

namespace Modules\Reports\Filament\CoreApp\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\InvoiceItem;

final class TopProductsWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    // protected int|string|array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Top 10 Products'))
            ->description(__('Based on sales invoices issued this year.'))
            ->query(
                function (): Builder {
                    $sub = DB::table('sales_invoice_items')
                        ->select([
                            DB::raw('MIN(sales_invoice_items.id) as id'),
                            DB::raw('SUM(sales_invoice_items.quantity) as qty_sold'),
                            DB::raw('SUM(sales_invoice_items.total) as revenue'),
                            'sales_invoice_items.product_name',
                        ])
                        ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.invoice_id')
                        ->where('sales_invoices.company_id', filament()->getTenant()?->id)
                        ->whereIn('sales_invoices.status', [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value])
                        ->whereNull('sales_invoices.deleted_at')
                        ->where('sales_invoices.issue_date', '>=', now()->startOfYear())
                        ->groupBy('sales_invoice_items.product_name')
                        ->orderByDesc(DB::raw('SUM(sales_invoice_items.total)'))
                        ->limit(10);

                    // Wrap in a subquery aliased as the model's table so that
                    // Filament's automatic ORDER BY "sales_invoice_items"."id"
                    // resolves to the MIN(id) column in the derived table.
                    return InvoiceItem::query()->fromSub($sub, 'sales_invoice_items');
                }
            )
            ->columns([
                TextColumn::make('product_name')
                    ->label(__('Product')),

                TextColumn::make('qty_sold')
                    ->label(__('Qty'))
                    ->numeric(decimalPlaces: 2)
                    ->alignRight(),

                MoneyTextColumn::make('revenue')
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->withoutDefaultSummarizer(),
            ])
            ->striped()
            ->paginated(false)
            ->columnManager(false)
            ->emptyStateIcon(Heroicon::OutlinedCube)
            ->emptyStateHeading(__('No sales yet'));
    }

    public function getColumnSpan(): int|string|array
    {
        return 2;
    }
}
