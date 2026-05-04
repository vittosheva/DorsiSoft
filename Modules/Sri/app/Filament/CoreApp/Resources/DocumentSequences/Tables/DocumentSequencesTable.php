<?php

declare(strict_types=1);

namespace Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\Establishment;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Models\DocumentSequence;
use Modules\Sri\Services\DocumentSequentialService;

final class DocumentSequencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Sequential numbering configurations for SRI-compliant documents issued by this company. Each sequence is assigned to an establishment, an emission point, and a document type. The current sequence number is used to generate the next document\'s access key for electronic authorization.'))
            ->columns([
                TextColumn::make('document_type')
                    ->sortable(),

                TextColumn::make('establishment_code')
                    ->badge()
                    ->alignment(Alignment::Center),

                TextColumn::make('emission_point_code')
                    ->badge()
                    ->alignment(Alignment::Center),

                TextColumn::make('last_sequential')
                    ->label(__('Last Issued'))
                    ->numeric()
                    ->sortable()
                    ->alignment(Alignment::Right),

                TextColumn::make('next_sequential')
                    ->label(__('Next Suggested'))
                    ->getStateUsing(fn (DocumentSequence $record): string => mb_str_pad((string) ($record->last_sequential + 1), 9, '0', STR_PAD_LEFT))
                    ->badge()
                    ->color('success')
                    ->alignment(Alignment::Right),

                TextColumn::make('history_count')
                    ->label(__('History'))
                    ->badge()
                    ->alignment(Alignment::Center),

                TextColumn::make('latestHistory.performedBy.name')
                    ->placeholder('—')
                    ->badge()
                    ->alignment(Alignment::Center),

                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->modalWidth('4xl'),

                Action::make('reset')
                    ->tooltip(__('Set new sequential'))
                    ->icon(Heroicon::ArrowPath)
                    ->color('warning')
                    ->requiresConfirmation(false)
                    ->modalHeading(__('Set new sequential'))
                    ->modalSubmitActionLabel(__('Change'))
                    ->schema([
                        Section::make(__('Reset Sequential Number'))
                            ->icon(Heroicon::ExclamationTriangle)
                            ->schema([
                                TextInput::make('last_sequential')
                                    ->label(__('Last Issued'))
                                    ->default(fn (DocumentSequence $record) => mb_str_pad((string) ($record->last_sequential), 9, '0', STR_PAD_LEFT))
                                    ->placeholder(__('None'))
                                    ->dehydrated(false)
                                    ->readOnly()
                                    ->columnSpan(4),

                                TextInput::make('new_start')
                                    ->label(__('Next Sequential Number'))
                                    ->helperText(__('The next issued document will use this number.'))
                                    ->default(fn (DocumentSequence $record) => $record->last_sequential + 1)
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->integer()
                                    ->autofocus()
                                    ->columnSpan(4),

                                Textarea::make('reason')
                                    ->helperText(__('Required for audit trail.'))
                                    ->required()
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12),
                    ])
                    ->action(function (DocumentSequence $record, array $data): void {
                        app(DocumentSequentialService::class)->reset(
                            companyId: $record->company_id,
                            establishmentCode: $record->establishment_code,
                            emissionPointCode: $record->emission_point_code,
                            documentType: $record->document_type,
                            newStart: (int) $data['new_start'],
                            reason: (string) $data['reason'],
                            performedBy: Auth::id(),
                        );

                        Notification::make()
                            ->success()
                            ->title(__('Sequential number reset successfully'))
                            ->send();
                    }),

                ViewAction::make()
                    ->icon(Heroicon::OutlinedClock)
                    ->tooltip(__('View history changes'))
                    ->disabled(fn (DocumentSequence $record) => $record->history_count === 0),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->options(SriDocumentTypeEnum::class)
                    ->searchable(),

                Filter::make('location')
                    ->schema([
                        Select::make('establishment_code')
                            ->label(__('Establishment'))
                            ->options(fn (): array => Establishment::query()
                                ->select('code')
                                ->where('is_active', true)
                                ->orderBy('code', 'desc')
                                ->pluck('code', 'code')
                                ->toArray())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('emission_point_code', null)),

                        Select::make('emission_point_code')
                            ->label(__('Emission Point'))
                            ->options(function (Get $get): array {
                                $establishmentCode = $get('establishment_code');

                                if (blank($establishmentCode)) {
                                    return [];
                                }

                                $establishmentId = Establishment::query()
                                    ->select('id')
                                    ->where('is_active', true)
                                    ->where('code', (string) $establishmentCode)
                                    ->value('id');

                                if (! $establishmentId) {
                                    return [];
                                }

                                return EmissionPoint::query()
                                    ->select('code')
                                    ->where('is_active', true)
                                    ->where('establishment_id', $establishmentId)
                                    ->orderBy('code', 'desc')
                                    ->pluck('code', 'code')
                                    ->toArray();
                            })
                            ->searchable()
                            ->disabled(fn (Get $get): bool => blank($get('establishment_code'))),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['establishment_code'] ?? null, fn (Builder $q, string $v) => $q->where('establishment_code', $v))
                            ->when($data['emission_point_code'] ?? null, fn (Builder $q, string $v) => $q->where('emission_point_code', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['establishment_code'] ?? null) {
                            $indicators[] = Indicator::make(__('Establishment: :code', ['code' => $data['establishment_code']]))
                                ->removeField('establishment_code');
                        }

                        if ($data['emission_point_code'] ?? null) {
                            $indicators[] = Indicator::make(__('Emission Point: :code', ['code' => $data['emission_point_code']]))
                                ->removeField('emission_point_code');
                        }

                        return $indicators;
                    })
                    ->columns(2)
                    ->columnSpan(4),
            ])
            ->toolbarActions([])
            ->paginated(false);
    }
}
