<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Components\FusedGroups;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Support\Forms\Concerns\HasSriEstablishmentFields;

final class SequenceEmissionFusedGroup extends FusedGroup
{
    use HasSriEstablishmentFields;

    protected SriDocumentTypeEnum $documentType;

    public static function makeForDocumentType(SriDocumentTypeEnum $documentType): static
    {
        $instance = new self();
        $instance->documentType = $documentType;
        $instance->setUpFusedGroup();

        return $instance;
    }

    public static function getDefaultName(): ?string
    {
        return 'sequence_emission';
    }

    protected function setUpFusedGroup(): void
    {
        $this
            ->label(__('Sequence Emission'))
            ->schema([
                Select::make('establishment_code')
                    ->options(fn (): array => self::resolveEstablishmentOptions())
                    ->live()
                    ->afterStateUpdated(self::resetSequenceOnEstablishmentChange())
                    ->placeholder(__('Establishment'))
                    ->required(),

                Select::make('emission_point_code')
                    ->options(fn (Get $get): array => self::resolveEmissionPointOptions($get('establishment_code')))
                    ->disabled(fn (Get $get): bool => blank($get('establishment_code')))
                    ->live()
                    ->afterStateUpdated(function ($state, $old, Get $get, Set $set) {
                        $operation = 'create';
                        $closure = self::suggestSequentialOnEmissionPointChange($this->documentType);
                        $closure($state, $get, $set, $operation);
                    })
                    ->placeholder(__('Emission Point'))
                    ->required(),

                TextInput::make('sequential_number')
                    ->maxLength(9)
                    ->disabled()
                    ->dehydrated()
                    ->live(onBlur: true)
                    ->placeholder(__('Sequential number'))
                    ->required(),
            ])
            ->columns(3)
            ->columnSpanFull();
    }
}
