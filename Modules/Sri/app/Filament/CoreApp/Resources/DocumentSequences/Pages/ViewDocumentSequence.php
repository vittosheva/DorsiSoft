<?php

declare(strict_types=1);

namespace Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\DocumentSequenceResource;
use Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\RelationManagers\SequenceHistoryRelationManager;
use Modules\Sri\Models\DocumentSequence;
use Modules\Sri\Services\DocumentSequentialService;

final class ViewDocumentSequence extends BaseViewRecord
{
    protected static string $resource = DocumentSequenceResource::class;

    public function getRelationManagers(): array
    {
        return [
            SequenceHistoryRelationManager::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        /** @var DocumentSequence $record */
        $record = $this->getRecord();

        return [
            Action::make('reset')
                ->label(__('Set new sequential'))
                ->icon(Heroicon::ArrowPath)
                ->color('warning')
                ->schema([
                    Grid::make()
                        ->schema([
                            TextInput::make('last_sequential')
                                ->label(__('Last Issued'))
                                ->placeholder(__('None'))
                                ->dehydrated(false)
                                ->readOnly(),

                            TextInput::make('new_start')
                                ->label(__('Next Sequential Number'))
                                ->helperText(__('The next issued document will use this number.'))
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->integer()
                                ->default(fn () => (int) $record->last_sequential + 1)
                                ->autofocus(),

                            Textarea::make('reason')
                                ->helperText(__('Required for audit trail.'))
                                ->required()
                                ->maxLength(500)
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data): void {
                    /** @var DocumentSequence $record */
                    $record = $this->getRecord();

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
                        ->title(__('Sequential reset successfully'))
                        ->send();

                    $this->refreshFormData(['last_sequential']);
                }),
        ];
    }
}
