<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Modules\Accounting\Enums\FiscalPeriodStatusEnum;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;

final class FiscalPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Fiscal periods defined for this company\'s accounting calendar. Each period corresponds to a month and year within a fiscal year and controls which dates are open for transaction posting. Closing a period prevents further modifications to its accounting entries.'))
            ->columns([
                TextColumn::make('name')
                    ->sortable(),
                TextColumn::make('year')
                    ->alignment(Alignment::Center)
                    ->sortable(),
                TextColumn::make('month')
                    ->formatStateUsing(fn ($state) => is_numeric($state) ? ucfirst(Carbon::createFromDate(null, (int) $state, null)->isoFormat('MMMM')) : ($state ?? '')),
                TextColumn::make('start_date')
                    ->date(),
                TextColumn::make('end_date')
                    ->date(),
                TextColumn::make('status')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->sortable(),
                CreatedByTextColumn::make(),
                CreatedAtTextColumn::make(),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->options(array_combine(range(date('Y') - 5, date('Y') + 5), range(date('Y') - 5, date('Y') + 5))),
                SelectFilter::make('month')
                    ->options(array_combine(range(1, 12), [
                        __('January'),
                        __('February'),
                        __('March'),
                        __('April'),
                        __('May'),
                        __('June'),
                        __('July'),
                        __('August'),
                        __('September'),
                        __('October'),
                        __('November'),
                        __('December'),
                    ])),
                DateRangeFilter::make('start_date'),
                DateRangeFilter::make('end_date'),
                SelectFilter::make('status')
                    ->options(FiscalPeriodStatusEnum::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modal()
                    ->modalWidth(Width::FourExtraLarge),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}
