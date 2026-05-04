<?php

declare(strict_types=1);

namespace Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Sri\Models\DocumentSequenceHistory;

final class SequenceHistoryRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'history';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Historical log of sequence number changes for this document series. Each entry records the previous and new sequence value along with the date and reason for the adjustment. This history ensures traceability and supports SRI compliance audits for document numbering continuity.'))
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('performedBy:id,name'))
            ->heading(__('History'))
            ->modelLabel(__('Entry'))
            ->pluralModelLabel(__('Entries'))
            ->columns([
                TextColumn::make('event')
                    ->formatStateUsing(fn (DocumentSequenceHistory $record): string => __($record->event))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'reset' => 'warning',
                        'record' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('previous_value')
                    ->label(__('Previous'))
                    ->numeric()
                    ->alignment(Alignment::Right),

                TextColumn::make('new_value')
                    ->label(__('New'))
                    ->numeric()
                    ->alignment(Alignment::Right),

                TextColumn::make('reason')
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn (DocumentSequenceHistory $record): ?string => $record->reason),

                TextColumn::make('performedBy.name')
                    ->placeholder('—'),

                CreatedAtTextColumn::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->headerActions([])
            ->toolbarActions([]);
    }
}
