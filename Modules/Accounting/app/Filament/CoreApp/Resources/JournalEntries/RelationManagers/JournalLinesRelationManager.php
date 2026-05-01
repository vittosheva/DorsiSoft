<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\JournalEntry;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;

final class JournalLinesRelationManager extends BaseRelationManager
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
                Select::make('account_id')
                    ->options(fn () => ChartOfAccount::query()
                        ->leafAccounts()
                        ->active()
                        ->orderBy('code')
                        ->get()
                        ->mapWithKeys(fn ($a) => [$a->id => "{$a->code} — {$a->name}"]))
                    ->required()
                    ->searchable()
                    ->columnSpanFull(),

                TextInput::make('description')
                    ->maxLength(300)
                    ->columnSpanFull(),

                TextInput::make('debit')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->prefix('$'),

                TextInput::make('credit')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->prefix('$'),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('The journal lines for this entry. Only entries in draft status can be edited.'))
            ->modelLabel(__('Journal Line'))
            ->columns([
                TextColumn::make('line_number')
                    ->label('#')
                    ->alignCenter()
                    ->width(40),

                TextColumn::make('account.code')
                    ->label(__('Account code'))
                    ->fontFamily('mono'),

                TextColumn::make('account.name')
                    ->label(__('Account name')),

                TextColumn::make('description')
                    ->placeholder('—')
                    ->limit(40),

                MoneyTextColumn::make('debit'),

                MoneyTextColumn::make('credit'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord() instanceof JournalEntry
                        && $this->getOwnerRecord()->isDraft()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord() instanceof JournalEntry
                        && $this->getOwnerRecord()->isDraft()),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord() instanceof JournalEntry
                        && $this->getOwnerRecord()->isDraft()),
            ]);
    }
}
