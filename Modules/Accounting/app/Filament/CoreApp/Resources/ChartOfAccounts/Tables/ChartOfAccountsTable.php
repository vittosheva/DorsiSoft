<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\Accounting\Enums\AccountTypeEnum;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;

final class ChartOfAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('The chart of accounts is a structured listing of all the accounts used in the company\'s accounting system, organized hierarchically by account type and nature. It serves as the foundation for recording and categorizing all financial transactions, enabling accurate financial reporting and analysis. Each account in the chart has a unique code, name, type (e.g., asset, liability, equity, revenue, expense), and nature (debit or credit), which together define how transactions are recorded and how they impact the company\'s financial statements.'))
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge(),

                TextColumn::make('nature')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('level')
                    ->alignCenter(),

                IconColumn::make('is_control')
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('allows_entries')
                    ->boolean()
                    ->alignCenter(),

                IsActiveColumn::make('is_active'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(AccountTypeEnum::options()),
                TernaryFilter::make('allows_entries'),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
