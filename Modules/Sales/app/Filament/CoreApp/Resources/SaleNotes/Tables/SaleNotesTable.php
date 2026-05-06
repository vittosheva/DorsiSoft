<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\CustomerNameTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\Core\Support\Tables\Filters\StatusFilter;
use Modules\Sales\Enums\SaleNoteStatusEnum;
use Modules\Sales\Models\SaleNote;
use Modules\Sales\Support\Tables\Filters\CustomerFilter;
use Modules\Sales\Support\Tables\Filters\SellerFilter;

final class SaleNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->date('d/m/Y')
                    ->sortable(),

                CustomerNameTextColumn::make('customer_name'),

                TextColumn::make('seller_name')
                    ->toggleable(isToggledHiddenByDefault: true),

                MoneyTextColumn::make('total')
                    ->currencyCode(fn (?SaleNote $record): string => $record?->currency_code ?? ''),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SaleNoteStatusEnum $state) => $state->getColor())
                    ->alignment(Alignment::Center),

                CreatedByTextColumn::make('created_by'),
                CreatedAtTextColumn::make('created_at'),
            ])
            ->filters([
                CustomerFilter::make('customer'),
                DateRangeFilter::make('issue_date'),
                SellerFilter::make('seller'),
                StatusFilter::make('status')
                    ->options(SaleNoteStatusEnum::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                GeneratePdfAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
