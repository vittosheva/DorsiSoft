<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Models\DocumentSequence;
use Modules\Sri\Services\DocumentSequentialService;
use Modules\System\Models\DocumentType;

final class ResetSequentialNumberAction extends Action
{
    protected SriDocumentTypeEnum $documentType;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->icon(Heroicon::ArrowPath)
            ->tooltip(__('Set new sequential'))
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
                            /* ->default(function (?Model $record, Set $set) {
                                $documentSequence = $this->getDocumentSequence($record, $set);
                                $lastSequential = $documentSequence?->last_sequential ?? 0;
                                return $this->buildSequentialNumber($lastSequential);
                            }) */
                            ->placeholder(__('None'))
                            ->dehydrated(false)
                            ->readOnly()
                            ->columnSpan(4),

                        TextInput::make('new_start')
                            ->label(__('Next Sequential Number'))
                            ->helperText(__('The next issued document will use this number.'))
                            ->default(fn (Get $get) => $this->buildSequentialNumber($get('last_sequential') + 1))
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

                        Hidden::make('document_type')
                            ->default(fn (?Model $record) => $record?->documentType?->code ?? null),
                    ])
                    ->columns(12),
            ])
            ->action(function (array $data, ?Model $record): void {
                app(DocumentSequentialService::class)->reset(
                    companyId: $record->company_id,
                    establishmentCode: $record->establishment_code,
                    emissionPointCode: $record->emission_point_code,
                    documentType: SriDocumentTypeEnum::tryFrom($data['document_type']),
                    newStart: (int) $data['new_start'],
                    reason: (string) $data['reason'],
                    performedBy: Auth::id(),
                );

                // Despachar evento que InvoiceForm escuche
                $this->getLivewire()->dispatch('sequential-updated', ['sequential_number' => $this->buildSequentialNumber((int) $data['new_start'])]);

                Notification::make()
                    ->title(__('Sequential number reset successfully'))
                    ->success()
                    ->send();
            })
            ->hidden(function ($operation, Get $get) {
                return $operation === 'view' || blank($get('establishment_code')) || blank($get('emission_point_code'));
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'reset_sequential_number';
    }

    public function setSriDocumentType(SriDocumentTypeEnum $documentType): static
    {
        $this->documentType = $documentType;

        return $this;
    }

    private function getDocumentSequence(?Model $record, Set $set): ?DocumentSequence
    {
        if (! $record) {
            $documentTypeCode = DocumentType::query()->where('code', $this->documentType)->value('id');
            $companyId = filament()->getTenant()->id;
            $establishmentCode = $get('establishment_code');
            $emissionPointCode = $get('emission_point_code');
        } else {
            $record = $record->loadMissing('documentType:id,code,name');
            $documentTypeCode = $record->documentType?->code ?? null;
            $companyId = $record->company_id;
            $establishmentCode = $record->establishment_code;
            $emissionPointCode = $record->emission_point_code;
        }

        $set('document_type', $documentTypeCode);

        return DocumentSequence::query()
            ->select(['id', 'last_sequential'])
            ->where('company_id', $companyId)
            ->where('establishment_code', $establishmentCode)
            ->where('emission_point_code', $emissionPointCode)
            ->where('document_type', $documentTypeCode)
            ->first();
    }

    private function buildSequentialNumber(int $sequential): string
    {
        return mb_str_pad((string) $sequential, 9, '0', STR_PAD_LEFT);
    }
}
