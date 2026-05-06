<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\People\Support\Forms\Selects\CustomerBusinessPartnerSelect;
use Modules\People\Support\Forms\Selects\SellerUserSelect;
use Modules\Sales\Enums\SaleNoteStatusEnum;

final class SaleNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                CodeTextColumn::make('code')->sortable(),
                TextColumn::make('issue_date')->date('d/m/Y')->sortable(),
                TextColumn::make('customer_name'),
                TextColumn::make('seller_name')->toggleable(isToggledHiddenByDefault: true),
                MoneyTextColumn::make('total'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SaleNoteStatusEnum $state) => $state->getColor()),
                CreatedByTextColumn::make('created_by'),
                CreatedAtTextColumn::make('created_at'),
            ])
            ->filters([
                Filter::make('customer_name')
                    ->schema([
                        CustomerBusinessPartnerSelect::make('business_partner_id'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['business_partner_id'] ?? null,
                        fn (Builder $q, $id) => $q->where('business_partner_id', $id)
                    ))
                    ->columnSpan(3),
                DateRangeFilter::make('issue_date'),
                Filter::make('seller_id')
                    ->schema([
                        SellerUserSelect::make('seller_id'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['seller_id'] ?? null,
                        fn (Builder $q, $id) => $q->where('seller_id', $id)
                    ))
                    ->columnSpan(3),
                SelectFilter::make('status')
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
