<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\TextInputs;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Actions\ValidateSriAction;
use Modules\Core\Support\Sri\SriPayloadMapper;
use Modules\People\Models\BusinessPartner;
use Modules\Sri\Enums\SriTaxpayerTypeEnum;
use Modules\Sri\Services\Sri\Contracts\SriServiceInterface;

final class IdentificationNumberTextInput extends TextInput
{
    protected ?string $identificationTypeStatePath = null;

    /**
     * @var array<int, string>|null
     */
    protected ?array $sriAllowedFields = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->identificationTypeStatePath('identification_type')
            ->prefixIcon(fn (Get $get): Heroicon => match ($this->currentType($get)) {
                'ruc' => Heroicon::BuildingOffice2,
                'cedula' => Heroicon::Identification,
                'passport' => Heroicon::BookOpen,
                default => Heroicon::FingerPrint,
            })
            ->maxLength(fn (Get $get): int => match ($this->currentType($get)) {
                'ruc' => 13,
                'cedula' => 10,
                default => 30,
            })
            ->minLength(fn (Get $get): ?int => match ($this->currentType($get)) {
                'ruc', 'cedula' => null,
                default => null,
            })
            ->rule(function (Get $get): ?callable {
                return match ($this->currentType($get)) {
                    'ruc' => $this->rucValidationRule(),
                    'cedula' => $this->cedulaValidationRule(),
                    default => null,
                };
            })
            ->suffixActions([$this->getSriAction()])
            ->dehydrateStateUsing(fn (?string $state): string => preg_replace('/\s+/', '', $state ?? '') ?? '')
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdatedJs(
                <<<'JS'
                    $set('identification_number', $get('identification_number') ? $get('identification_number').replace(/\s+/g, '') : '');
                    $set('hideCallout', false);
                JS
            )
            ->afterStateUpdated(function (Set $set, Get $get, ?Model $record): void {
                $set('hideCallout', false);
                $identType = $get('identification_type');
                $identNumber = $get('identification_number');

                if (blank($identType) || blank($identNumber)) {
                    $set('_duplicate_partner_id', null);
                    $set('_duplicate_partner_name', '');

                    return;
                }

                $duplicate = self::findDuplicatePartner($get, $record);
                $set('_duplicate_partner_id', $duplicate?->getKey());
                $set('_duplicate_partner_name', $duplicate?->legal_name ?? '');
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'identification_number';
    }

    public function identificationTypeStatePath(string $path): static
    {
        $this->identificationTypeStatePath = $path;

        return $this;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function uniqueAmong(string $modelClass, string $tenantColumn = 'company_id'): static
    {
        $this->rule(function (Get $get, ?Model $record) use ($modelClass, $tenantColumn): ?callable {
            $identType = $this->currentType($get);
            $tenantId = Filament::getTenant()?->getKey();

            return function (string $attribute, mixed $value, callable $fail) use ($identType, $tenantId, $record, $modelClass, $tenantColumn): void {
                $exists = $modelClass::withTrashed()
                    ->where($tenantColumn, $tenantId)
                    ->where('identification_type', $identType)
                    ->where('identification_number', $value)
                    ->when($record, fn ($q) => $q->whereNot('id', $record->getKey()))
                    ->exists();

                if ($exists) {
                    $fail(__('There is already an entity with this type and identification within the company.'));
                }
            };
        });

        return $this;
    }

    /**
     * @param  array<int, string>|null  $fields
     */
    public function sriAllowedFields(?array $fields): static
    {
        $this->sriAllowedFields = $fields === null
            ? null
            : array_values(array_unique(array_map(static fn (string $field): string => mb_trim($field), $fields)));

        return $this;
    }

    private static function findDuplicatePartner(Get $get, ?Model $record): ?BusinessPartner
    {
        $companyId = Filament::getTenant()?->getKey();
        $identType = $get('identification_type');
        $identNumber = $get('identification_number');

        if (blank($identType) || blank($identNumber) || blank($companyId)) {
            return null;
        }

        return BusinessPartner::withTrashed()
            ->select(['id', 'legal_name'])
            ->where('company_id', $companyId)
            ->where('identification_type', $identType)
            ->where('identification_number', $identNumber)
            ->when($record, fn ($q) => $q->whereNot('id', $record->getKey()))
            ->first();
    }

    private function currentType(Get $get): string
    {
        if ($this->identificationTypeStatePath === null) {
            return '';
        }

        return (string) ($get($this->identificationTypeStatePath) ?? '');
    }

    private function rucValidationRule(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            $normalized = preg_replace('/\D/', '', (string) $value) ?? '';

            if (mb_strlen($normalized) !== 13) {
                $fail(__('The RUC must be exactly 13 digits.'));
            }
        };
    }

    private function cedulaValidationRule(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            $normalized = preg_replace('/\D/', '', (string) $value) ?? '';

            if (mb_strlen($normalized) !== 10) {
                $fail(__('The ID card must be exactly 10 digits.'));

                return;
            }

            $province = (int) mb_substr($normalized, 0, 2);

            if ($province < 1 || $province > 24) {
                $fail(__('The ID card has an invalid province code.'));

                return;
            }

            $coefficients = [2, 1, 2, 1, 2, 1, 2, 1, 2];
            $sum = 0;

            for ($i = 0; $i < 9; $i++) {
                $product = (int) $normalized[$i] * $coefficients[$i];
                $sum += $product >= 10 ? $product - 9 : $product;
            }

            $checkDigit = (10 - ($sum % 10)) % 10;

            if ($checkDigit !== (int) $normalized[9]) {
                $fail(__('The ID card number is invalid.'));
            }
        };
    }

    private function getSriAction(): ValidateSriAction
    {
        return ValidateSriAction::make('validateSri')
            ->invalidTitle('Enter a valid identification number to validate.')
            ->failureTitle('Unable to validate the identification with the SRI.')
            ->successTitle('Identification validated successfully in the SRI.')
            ->noInformationTitle('No information found for this identification in the SRI.')
            ->isValueValidUsing(function (Get $get): bool {
                return $this->hasValidValueForSri(
                    $this->currentType($get),
                    preg_replace('/\D/', '', (string) $get($this->getName())) ?? ''
                );
            })
            ->performValidationUsing(function (Get $get, Set $set): bool {
                $type = $this->currentType($get);
                $value = preg_replace('/\D/', '', (string) $get($this->getName())) ?? '';
                $rucToQuery = $type === 'cedula' ? $value.'001' : $value;

                return $this->hydrateFormDataFromSri($rucToQuery, $type, $set, false);
            })/*
            ->visibleWhenReadyUsing(fn(Get $get): bool => ($get('hideCallout') || ! $get('_duplicate_partner_id'))
                && $this->hasValidValueForSri(
                    $this->currentType($get),
                    preg_replace('/\D/', '', (string) $get($this->getName())) ?? ''
                )) */;
    }

    private function hasValidValueForSri(string $type, string $value): bool
    {
        return match ($type) {
            'ruc' => mb_strlen($value) === 13,
            'cedula' => mb_strlen($value) === 10,
            default => false,
        };
    }

    private function hydrateFormDataFromSri(string $ruc, string $type, callable $set, bool $withEstablishments = true): bool
    {
        $data = $this->consultarContribuyente($ruc);

        if ($data === []) {
            return false;
        }

        $payloadMapper = $this->sriPayloadMapper();
        $hasPopulatedFields = false;

        $hasPopulatedFields = $this->setWhenAllowed($set, 'legal_name', $payloadMapper->extractStringValue($data, [
            'legal_name',
            'razonSocial',
            'razon_social',
            'nombreLegal',
            'nombre_legal',
        ])) || $hasPopulatedFields;

        if ($type === 'ruc') {
            $hasPopulatedFields = $this->setWhenAllowed($set, 'trade_name', $payloadMapper->extractStringValue($data, [
                'trade_name',
                'nombreComercial',
                'nombre_comercial',
                'razonSocial',
                'razon_social',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'business_activity', $payloadMapper->extractStringValue($data, [
                'business_activity',
                'actividadEconomicaPrincipal',
                'actividad_economica_principal',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'tax_regime', $payloadMapper->normalizeTaxRegime(
                $payloadMapper->extractStringValue($data, ['tax_regime', 'regimen'])
            )) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'is_accounting_required', $payloadMapper->extractBooleanValue($data, [
                'is_accounting_required',
                'obligadoLlevarContabilidad',
                'obligado_llevar_contabilidad',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'is_special_taxpayer', $payloadMapper->extractBooleanValue($data, [
                'is_special_taxpayer',
                'contribuyenteEspecial',
                'contribuyente_especial',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'is_withholding_agent', $payloadMapper->extractBooleanValue($data, [
                'is_withholding_agent',
                'agenteRetencion',
                'agente_retencion',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'is_shell_company', $payloadMapper->extractBooleanValue($data, [
                'is_shell_company',
                'contribuyenteFantasma',
                'contribuyente_fantasma',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'has_nonexistent_transactions', $payloadMapper->extractBooleanValue($data, [
                'has_nonexistent_transactions',
                'transaccionesInexistente',
                'transacciones_inexistente',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'contributor_category', $payloadMapper->extractStringValue($data, [
                'contributor_category',
                'categoria',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'ceased_activities_at', $payloadMapper->extractStringValue($data, [
                'ceased_activities_at',
                'informacionFechasContribuyente.fechaCese',
                'informacion_fechas_contribuyente.fecha_cese',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'restarted_activities_at', $payloadMapper->extractStringValue($data, [
                'restarted_activities_at',
                'informacionFechasContribuyente.fechaReinicioActividades',
                'informacion_fechas_contribuyente.fecha_reinicio_actividades',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'sri_updated_at', $payloadMapper->extractStringValue($data, [
                'sri_updated_at',
                'informacionFechasContribuyente.fechaActualizacion',
                'informacion_fechas_contribuyente.fecha_actualizacion',
            ])) || $hasPopulatedFields;

            $hasPopulatedFields = $this->setWhenAllowed($set, 'suspension_cancellation_reason', $payloadMapper->extractStringValue($data, [
                'suspension_cancellation_reason',
                'motivoCancelacionSuspension',
                'motivo_cancelacion_suspension',
            ])) || $hasPopulatedFields;

            $legalRepresentatives = $this->mapLegalRepresentatives($data);
            $hasPopulatedFields = $this->setWhenAllowed($set, 'legal_representatives', $legalRepresentatives) || $hasPopulatedFields;
        }

        $hasPopulatedFields = $this->setWhenAllowed($set, 'contributor_status', $payloadMapper->extractStringValue($data, [
            'contributor_status',
            'estadoContribuyenteRuc',
            'estado_contribuyente_ruc',
        ])) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenAllowed($set, 'taxpayer_type', SriTaxpayerTypeEnum::normalize(
            $payloadMapper->extractStringValue($data, ['taxpayer_type', 'tipoContribuyente', 'tipo_contribuyente'])
        )) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenAllowed($set, 'started_activities_at', $payloadMapper->extractStringValue($data, [
            'started_activities_at',
            'informacionFechasContribuyente.fechaInicioActividades',
            'informacion_fechas_contribuyente.fecha_inicio_actividades',
        ])) || $hasPopulatedFields;

        return $hasPopulatedFields;
    }

    private function setWhenAllowed(callable $set, string $field, mixed $value): bool
    {
        if (! $this->canSetSriField($field)) {
            return false;
        }

        if ($value === null) {
            return false;
        }

        $set($field, $value);

        return true;
    }

    private function canSetSriField(string $field): bool
    {
        if ($this->sriAllowedFields === null) {
            return true;
        }

        return in_array($field, $this->sriAllowedFields, true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{identification: string, name: string}>
     */
    private function mapLegalRepresentatives(array $data): array
    {
        $representatives = $this->sriPayloadMapper()->extractArrayValue($data, [
            'legal_representatives',
            'representantesLegales',
            'representantes_legales',
        ]);

        if (! is_array($representatives)) {
            return [];
        }

        if (
            array_key_exists('identificacion', $representatives)
            || array_key_exists('identification', $representatives)
            || array_key_exists('nombre', $representatives)
            || array_key_exists('name', $representatives)
        ) {
            $representatives = [$representatives];
        }

        return collect($representatives)
            ->filter(fn (mixed $representative): bool => is_array($representative))
            ->map(function (array $representative): array {
                return [
                    'identification' => mb_trim((string) (
                        $representative['identificacion']
                        ?? $representative['identification']
                        ?? $representative['numeroIdentificacion']
                        ?? $representative['numero_identificacion']
                        ?? $representative['ruc']
                        ?? $representative['cedula']
                        ?? ''
                    )),
                    'name' => mb_trim((string) (
                        $representative['nombre']
                        ?? $representative['name']
                        ?? $representative['razonSocial']
                        ?? $representative['razon_social']
                        ?? $representative['nombresApellidos']
                        ?? $representative['nombres_apellidos']
                        ?? ''
                    )),
                ];
            })
            ->filter(fn (array $representative): bool => $representative['identification'] !== '' || $representative['name'] !== '')
            ->values()
            ->all();
    }

    private function consultarContribuyente(string $ruc): array
    {
        /** @var SriServiceInterface $sriService */
        $sriService = app(SriServiceInterface::class);

        return $sriService->consultarContribuyente($ruc);
    }

    private function sriPayloadMapper(): SriPayloadMapper
    {
        return app(SriPayloadMapper::class);
    }
}
