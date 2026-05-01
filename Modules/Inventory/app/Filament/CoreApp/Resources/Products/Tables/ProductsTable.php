<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Products\Tables;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Services\FileStoragePathService;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Filters\IsActiveFilter;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Enums\BarcodeTypeEnum;
use Modules\Inventory\Enums\ProductTypeEnum;
use Modules\Inventory\Models\Product;
use Modules\System\Enums\TaxCalculationTypeEnum;

final class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Products and services configured for this company. These are used when creating sales documents such as invoices, quotations, and sales orders. Each product can be associated with taxes, a category, a brand, and a unit of measure. Each company manages its own product catalog independently.'))
            ->columns([
                ImageColumn::make('image_url')
                    ->disk(fn () => FileStoragePathService::getDisk(FileTypeEnum::ProductImages))
                    ->visibility(fn () => FileStoragePathService::getVisibility(FileTypeEnum::ProductImages))
                    ->circular()
                    ->alignment(Alignment::Center)
                    ->toggleable(isToggledHiddenByDefault: true),

                CodeTextColumn::make('code'),

                TextColumn::make('name')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge(),

                TextColumn::make('barcode')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('has_qr_code')
                    ->label('QR')
                    ->boolean()
                    ->trueIcon(Heroicon::QrCode)
                    ->falseIcon(Heroicon::Minus)
                    ->getStateUsing(fn (Product $record): bool => $record->barcode_type === BarcodeTypeEnum::Qr && filled($record->barcode))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('category.name')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('unit.name')
                    ->placeholder('—'),

                MoneyTextColumn::make('sale_price')
                    ->label(__('Price'))
                    ->currencyCode(fn (): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD'),

                TextColumn::make('default_taxes')
                    ->label(__('Taxes'))
                    ->state(function (Product $record): string {
                        return $record->defaultTaxes()
                            ->map(function (Tax $tax): string {
                                $calculationType = $tax->calculation_type instanceof TaxCalculationTypeEnum
                                    ? $tax->calculation_type
                                    : TaxCalculationTypeEnum::tryFrom((string) $tax->calculation_type);

                                $formattedRate = $calculationType === TaxCalculationTypeEnum::Fixed
                                    ? '$'.number_format((float) $tax->rate, 2)
                                    : number_format((float) $tax->rate, 2).' %';

                                $type = $tax->type instanceof BackedEnum ? $tax->type->value : (string) $tax->type;

                                return "{$type}: {$formattedRate}";
                            })
                            ->implode(', ');
                    })
                    ->placeholder('—'),

                IsActiveColumn::make('is_active'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ProductTypeEnum::class),
                SelectFilter::make('categories')
                    ->relationship(
                        'category',
                        'name',
                        fn (Builder $query) => $query
                            ->select(['id', 'name'])
                            ->orderBy('name')
                            ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                    )
                    ->preload()
                    ->searchable()
                    ->multiple()
                    ->columnSpan(2),
                SelectFilter::make('unit_id')
                    ->label(__('Unit'))
                    ->relationship(
                        'unit',
                        'name',
                        fn (Builder $query) => $query
                            ->select(['id', 'name'])
                            ->orderBy('name')
                            ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                    )
                    ->preload()
                    ->searchable(),
                IsActiveFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
