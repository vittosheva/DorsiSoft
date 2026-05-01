<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Filters\CreatorFilter;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Users who have access to this company\'s account. Each user is assigned one or more roles that determine which modules and actions they can access. User management is independent per company in this multi-tenant system.'))
            ->columns([
                CodeTextColumn::make('code'),
                TextColumn::make('name')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $innerQuery) use ($search): void {
                            $innerQuery->where('name', 'like', "{$search}%");

                            if (mb_strlen($search) >= 3) {
                                $innerQuery->orWhereFullText('name', $search);
                            }
                        }),
                    ),
                TextColumn::make('email')
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->where('email', 'like', "{$search}%"),
                    ),
                TextColumn::make('roles.name')
                    ->badge(),
                TextColumn::make('email_verified_at')
                    ->placeholder(__('Not verified'))
                    ->tooltip(__('Click to change email verification'))
                    ->formatStateUsing(fn ($state): string => $state?->format('Y-m-d H:i:s'))
                    ->color(fn ($state): ?string => blank($state) ? 'gray' : null)
                    ->action(
                        Action::make('changeEmailVerification')
                            ->requiresConfirmation()
                            ->schema([
                                DateTimePicker::make('email_verified_at')
                                    ->label(__('Verified at'))
                                    ->seconds()
                                    ->required(),
                            ])
                            ->fillForm(fn ($record) => [
                                'email_verified_at' => $record->email_verified_at?->format('Y-m-d H:i:s') ?? now(),
                            ])
                            ->action(function ($record, array $data) {
                                $record->update($data);

                                Notification::make()
                                    ->title(__('Email verification updated successfully'))
                                    ->success()
                                    ->send();
                            }),
                    )
                    ->sortable(),
                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship(
                        'roles',
                        'name',
                        fn (Builder $query) => $query
                            ->select($query->qualifyColumns(['id', 'name']))
                            ->orderBy('name')
                            ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                    )
                    ->preload()
                    ->searchable()
                    ->multiple(),
                CreatorFilter::make('creator'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
