<?php

declare(strict_types=1);

namespace Modules\System\Filament\CoreApp\Resources\DocumentTypes\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\Establishment;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;

final class DocumentSeriesRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'series';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Document series');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('establishment_id')
                    ->options(fn () => Establishment::query()
                        ->whereBelongsTo(filament()->getTenant(), 'company')
                        ->pluck('name', 'id'))
                    ->nullable()
                    ->searchable(),

                TextInput::make('prefix')
                    ->maxLength(20)
                    ->placeholder('INT'),

                TextInput::make('current_sequence')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),

                TextInput::make('padding')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(10)
                    ->default(6)
                    ->required(),

                Toggle::make('auto_reset_yearly')
                    ->inline(false),

                Toggle::make('is_active')
                    ->inline(false)
                    ->default(true),
            ])
            ->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(__('Document series'))
            ->columns([
                TextColumn::make('establishment.name')
                    ->placeholder(__('All establishments')),

                TextColumn::make('prefix')
                    ->placeholder('—'),

                TextColumn::make('current_sequence')
                    ->numeric(),

                TextColumn::make('padding'),

                IconColumn::make('auto_reset_yearly')
                    ->boolean(),

                IsActiveColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
