<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxRules\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\RelationManagers\BaseRelationManager;

final class TaxRuleLinesRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'lines';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Lines');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),

                TextInput::make('from_amount')
                    ->numeric()
                    ->nullable(),

                TextInput::make('to_amount')
                    ->numeric()
                    ->nullable(),

                TextInput::make('excess_from')
                    ->numeric()
                    ->default(0),

                TextInput::make('rate')
                    ->label(__('Rate (%)'))
                    ->numeric()
                    ->suffix('%')
                    ->default(0)
                    ->required(),

                TextInput::make('fixed_amount')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),

                TextInput::make('description')
                    ->maxLength(150)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Individual conditions that compose this tax rule, each specifying a threshold, rate, or criterion used to evaluate tax applicability. Rule lines are evaluated in sequence to determine the applicable tax amount for a given transaction. Progressive income tax brackets are represented as ordered rule lines.'))
            ->modelLabel(__('Line'))
            ->recordTitleAttribute('description')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('from_amount')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('to_amount')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('rate')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2),

                TextColumn::make('fixed_amount')
                    ->money('USD'),

                TextColumn::make('description')
                    ->wrap(),
            ])
            ->headerActions([
                CreateAction::make(),
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
            ->defaultSort('sort_order');
    }
}
