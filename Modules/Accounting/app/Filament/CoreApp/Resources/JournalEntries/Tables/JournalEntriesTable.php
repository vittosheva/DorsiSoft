<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Accounting\Enums\JournalEntryStatusEnum;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\JournalEntryService;
use Modules\People\Models\User;

final class JournalEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('entry_date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('fiscalPeriod.name')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(fn (JournalEntry $record): string => $record->description),

                TextColumn::make('total_debit')
                    ->label(__('Debit'))
                    ->money('USD')
                    ->alignRight(),

                TextColumn::make('total_credit')
                    ->label(__('Credit'))
                    ->money('USD')
                    ->alignRight(),

                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(JournalEntryStatusEnum::class),

                SelectFilter::make('fiscal_period_id')
                    ->relationship('fiscalPeriod', 'name'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modal()
                    ->modalWidth(Width::FourExtraLarge),
                Action::make('approve')
                    ->tooltip(__('Approve'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->modalHeading(__('Approve Journal Entry'))
                    ->modalDescription(fn (JournalEntry $record): string => __(
                        'Approve entry :ref? This will post it to the ledger and update account balances.',
                        ['ref' => $record->reference]
                    ))
                    ->action(function (JournalEntry $record): void {
                        /** @var User $user */
                        $user = Filament::auth()->user();
                        app(JournalEntryService::class)->approve($record, $user);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (JournalEntry $record): bool => $record->canBeApproved()),

                Action::make('void')
                    ->tooltip(__('Void'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->modalHeading(__('Void Journal Entry'))
                    ->schema([
                        Textarea::make('void_reason')
                            // ->label(__('Reason'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (JournalEntry $record, array $data): void {
                        /** @var User $user */
                        $user = Filament::auth()->user();
                        app(JournalEntryService::class)->void($record, $user, $data['void_reason']);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (JournalEntry $record): bool => $record->canBeVoided()),

                EditAction::make()
                    ->visible(fn (JournalEntry $record): bool => $record->isDraft()),

                DeleteAction::make()
                    ->visible(fn (JournalEntry $record): bool => $record->isDraft()),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
