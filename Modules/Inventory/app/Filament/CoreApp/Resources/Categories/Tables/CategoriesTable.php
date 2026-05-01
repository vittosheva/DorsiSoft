<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Core\Support\Tables\Filters\IsActiveFilter;

final class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Product categories used to organize this company\'s catalog. Categories support a parent-child hierarchy for more granular classification. They are used as filters and grouping criteria in sales reports and product lists.'))
            ->columns([
                Stack::make([
                    CodeTextColumn::make('code'),

                    TextColumn::make('name')
                        ->weight(FontWeight::SemiBold)
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('parent.name')
                        ->label(__('Parent'))
                        ->badge()
                        ->placeholder('—'),

                    IsActiveColumn::make('is_active')
                        ->tooltip(null)
                        ->action(null),
                ]),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 5,
            ])
            ->filters([
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
