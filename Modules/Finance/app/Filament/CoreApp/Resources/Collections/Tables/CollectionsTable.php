<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Collections\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\CustomerNameTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Finance\Enums\CollectionMethodEnum;
use Modules\Finance\Models\Collection;
use Modules\Sales\Support\Tables\Filters\CustomerFilter;
use Modules\Sales\Support\Tables\Filters\VoidedFilter;
use Modules\Workflow\Support\Tables\Columns\ApprovalDecisionTextColumn;

final class CollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Payment collections registered for this company, recording amounts received from customers against outstanding invoices. Collections can be fully or partially allocated to one or more invoices. The available balance reflects the unallocated portion ready for future invoice matching.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('collection_date')
                    ->date('d/m/Y')
                    ->sortable(),

                CustomerNameTextColumn::make('customer_name')
                    ->description(fn (Model $record) => $record->businessPartner->identification_number ?? null),

                TextColumn::make('collection_method')
                    ->badge(),

                MoneyTextColumn::make('amount')
                    ->currencyCode(fn ($record): string => $record->currency_code),

                MoneyTextColumn::make('available_amount')
                    ->label(__('Available'))
                    ->state(fn (Collection $record): string => $record->available_amount)
                    ->currencyCode(fn (Collection $record): string => $record->currency_code)
                    ->withoutDefaultSummarizer()
                    ->sortable(
                        query: fn (Builder $query, string $direction): Builder => $query->orderByRaw(Collection::availableAmountSql().' '.mb_strtoupper($direction))
                    )
                    ->summarize([
                        Summarizer::make()
                            ->label(__('Available'))
                            ->using(function (QueryBuilder $query): float {
                                $availableAmount = $query
                                    ->selectRaw('COALESCE(SUM('.Collection::availableAmountSql('sales_collections').'), 0) as aggregate')
                                    ->value('aggregate');

                                return (float) $availableAmount;
                            }),
                    ]),

                TextColumn::make('voided_at')
                    ->label(__('Status'))
                    ->badge()
                    ->default(fn (Collection $record) => $record->isVoided() ? __('Voided') : __('Active'))
                    ->color(fn (Collection $record) => $record->isVoided() ? 'danger' : 'success'),

                ApprovalDecisionTextColumn::forFlow('authorization_approval', 'authorization'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                CustomerFilter::make('customer'),
                SelectFilter::make('collection_method')
                    ->options(CollectionMethodEnum::class),
                TernaryFilter::make('has_available_balance')
                    ->label(__('Available balance'))
                    ->placeholder(__('All collections'))
                    ->trueLabel(__('With available balance'))
                    ->falseLabel(__('Without available balance'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereRaw(Collection::availableAmountSql().' > 0'),
                        false: fn (Builder $query): Builder => $query->whereRaw(Collection::availableAmountSql().' = 0'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                VoidedFilter::make('voided'),
            ])
            ->recordActions([
                ViewAction::make()->modal(),
                EditAction::make()
                    ->visible(fn (Collection $record) => ! $record->isVoided()),
                DeleteAction::make()
                    ->visible(fn (Collection $record) => ! $record->isVoided()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
