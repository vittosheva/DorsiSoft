<?php

declare(strict_types=1);

namespace Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\Schemas;

use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\Establishment;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\DocumentSequenceResource;
use Modules\Sri\Models\DocumentSequence;

final class DocumentSequenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Document Sequence'))
                    ->icon(DocumentSequenceResource::getNavigationIcon())
                    ->schema([
                        Select::make('document_type')
                            ->options(fn (): array => self::resolveDocumentTypeOptions())
                            ->searchable()
                            ->required()
                            ->rules([
                                fn (Get $get, ?Model $record): Unique => Rule::unique((new DocumentSequence())->getTable(), 'document_type')
                                    ->ignore($record?->getKey())
                                    ->where('company_id', Filament::getTenant()?->getKey())
                                    ->where('establishment_code', (string) $get('establishment_code'))
                                    ->where('emission_point_code', (string) $get('emission_point_code')),
                            ]),

                        Select::make('establishment_code')
                            ->options(fn (): array => self::resolveEstablishmentOptions())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->exists(
                                table: (new Establishment())->getTable(),
                                column: 'code',
                                modifyRuleUsing: fn (Exists $rule): Exists => $rule
                                    ->where('company_id', Filament::getTenant()?->getKey())
                                    ->where('is_active', true),
                            )
                            ->afterStateUpdated(function (Set $set): void {
                                $set('emission_point_code', null);
                            }),

                        Select::make('emission_point_code')
                            ->options(fn (Get $get): array => self::resolveEmissionPointOptions($get('establishment_code')))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (Get $get): bool => blank($get('establishment_code')))
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if (blank($value) || blank($get('establishment_code'))) {
                                        return;
                                    }

                                    $establishmentId = self::resolveActiveEstablishmentId((string) $get('establishment_code'));

                                    if (! $establishmentId) {
                                        $fail(__('The selected emission point is invalid for the selected establishment.'));

                                        return;
                                    }

                                    $exists = EmissionPoint::query()
                                        ->select('id')
                                        ->where('company_id', Filament::getTenant()?->getKey())
                                        ->where('code', $value)
                                        ->where('is_active', true)
                                        ->where('establishment_id', $establishmentId)
                                        ->exists();

                                    if (! $exists) {
                                        $fail(__('The selected emission point is invalid for the selected establishment.'));
                                    }
                                },
                            ]),

                        TextInput::make('last_sequential')
                            ->label(__('Last Issued'))
                            ->dehydrated(false)
                            ->readOnly()
                            ->hiddenOn('create'),
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }

    /**
     * @return array<string, string>
     */
    private static function resolveEstablishmentOptions(): array
    {
        $companyId = Filament::getTenant()?->getKey();

        if (! $companyId) {
            return [];
        }

        return Establishment::query()
            ->select(['code', 'name'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code', 'desc')
            ->limit(config('dorsi.filament.select_filter_options_limit', 50))
            ->get()
            ->mapWithKeys(fn (Establishment $establishment): array => [
                $establishment->code => filled($establishment->name)
                    ? "{$establishment->code} - {$establishment->name}"
                    : $establishment->code,
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function resolveEmissionPointOptions(?string $establishmentCode): array
    {
        if (blank($establishmentCode)) {
            return [];
        }

        $companyId = Filament::getTenant()?->getKey();

        if (! $companyId) {
            return [];
        }

        $establishmentId = self::resolveActiveEstablishmentId($establishmentCode);

        if (! $establishmentId) {
            return [];
        }

        return EmissionPoint::query()
            ->select(['id', 'code', 'name', 'establishment_id'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('establishment_id', $establishmentId)
            ->orderBy('code', 'desc')
            ->limit(config('dorsi.filament.select_filter_options_limit', 50))
            ->get()
            ->mapWithKeys(fn (EmissionPoint $emissionPoint): array => [
                $emissionPoint->code => filled($emissionPoint->name)
                    ? "{$emissionPoint->code} - {$emissionPoint->name}"
                    : $emissionPoint->code,
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function resolveDocumentTypeOptions(): array
    {
        return collect(SriDocumentTypeEnum::cases())
            ->mapWithKeys(fn (SriDocumentTypeEnum $documentType): array => [
                $documentType->value => $documentType->getLabel(),
            ])
            ->all();
    }

    private static function resolveActiveEstablishmentId(?string $establishmentCode): ?int
    {
        if (blank($establishmentCode)) {
            return null;
        }

        $companyId = Filament::getTenant()?->getKey();

        if (! $companyId) {
            return null;
        }

        $establishmentId = Establishment::query()
            ->select('id')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('code', $establishmentCode)
            ->value('id');

        return filled($establishmentId) ? (int) $establishmentId : null;
    }
}
