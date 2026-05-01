<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\TextInputs;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Models\Company;
use Modules\Core\Support\Actions\ValidateSriAction;
use Modules\Core\Support\Sri\SriPayloadMapper;
use Modules\Sri\Enums\SriTaxpayerTypeEnum;
use Modules\Sri\Services\Sri\Contracts\SriServiceInterface;

final class RucTextInput extends TextInput
{
    /**
     * @var array<int, string>|null
     */
    protected ?array $sriAllowedFields = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->prefixIcon(Heroicon::BuildingOffice2)
            ->suffixActions([
                ValidateSriAction::make('validateSri')
                    ->invalidTitle('Enter a valid RUC to validate.')
                    ->failureTitle('Unable to validate the RUC with the SRI.')
                    ->successTitle('RUC validated successfully in the SRI.')
                    ->noInformationTitle('No information found for this RUC in the SRI.')
                    ->isValueValidUsing(fn (Get $get): bool => $this->hasValidRuc($this->normalizeRuc((string) $get($this->getName()))))
                    ->performValidationUsing(function (Get $get, Set $set): bool {
                        $ruc = $this->normalizeRuc((string) $get($this->getName()));

                        return $this->hydrateFormDataFromSri($ruc, $set, null, false);
                    }),
            ])
            ->required()
            ->minLength(13)
            ->maxLength(13)
            ->rule('digits:13')
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                $ruc = $this->normalizeRuc($state);

                if (! $this->hasValidRuc($ruc)) {
                    return;
                }
            })
            ->partiallyRenderAfterStateUpdated();
    }

    public static function getDefaultName(): ?string
    {
        return 'ruc';
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

    public function uniqueCompanyRuc(Closure|Company|null $ignorable = null): static
    {
        return $this->unique(table: Company::class, column: 'ruc', ignorable: $ignorable);
    }

    private function hydrateFormDataFromSri(string $ruc, callable $set, ?callable $get = null, bool $withEstablishments = true): bool
    {
        $data = $this->consultarContribuyente($ruc);

        if ($data === []) {
            return false;
        }

        $payloadMapper = $this->sriPayloadMapper();
        $hasPopulatedFields = false;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'legal_name',
            $payloadMapper->extractStringValue($data, [
                'legal_name',
                'razonSocial',
                'razon_social',
                'nombreLegal',
                'nombre_legal',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'trade_name',
            $payloadMapper->extractStringValue($data, [
                'trade_name',
                'nombreComercial',
                'nombre_comercial',
                'razonSocial',
                'razon_social',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'business_activity',
            $payloadMapper->extractStringValue($data, [
                'business_activity',
                'actividadEconomicaPrincipal',
                'actividad_economica_principal',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'tax_regime',
            $payloadMapper->normalizeTaxRegime(
                $payloadMapper->extractStringValue($data, [
                    'tax_regime',
                    'regimen',
                ])
            )
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'is_accounting_required',
            $payloadMapper->extractBooleanValue($data, [
                'is_accounting_required',
                'obligadoLlevarContabilidad',
                'obligado_llevar_contabilidad',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'is_special_taxpayer',
            $payloadMapper->extractBooleanValue($data, [
                'is_special_taxpayer',
                'contribuyenteEspecial',
                'contribuyente_especial',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'contributor_status',
            $payloadMapper->extractStringValue($data, [
                'contributor_status',
                'estadoContribuyenteRuc',
                'estado_contribuyente_ruc',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'taxpayer_type',
            SriTaxpayerTypeEnum::normalize(
                $payloadMapper->extractStringValue($data, [
                    'taxpayer_type',
                    'tipoContribuyente',
                    'tipo_contribuyente',
                ])
            )
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'contributor_category',
            $payloadMapper->extractStringValue($data, [
                'contributor_category',
                'categoria',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'is_withholding_agent',
            $payloadMapper->extractBooleanValue($data, [
                'is_withholding_agent',
                'agenteRetencion',
                'agente_retencion',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'is_shell_company',
            $payloadMapper->extractBooleanValue($data, [
                'is_shell_company',
                'contribuyenteFantasma',
                'contribuyente_fantasma',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'has_nonexistent_transactions',
            $payloadMapper->extractBooleanValue($data, [
                'has_nonexistent_transactions',
                'transaccionesInexistente',
                'transacciones_inexistente',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'started_activities_at',
            $payloadMapper->extractStringValue($data, [
                'started_activities_at',
                'informacionFechasContribuyente.fechaInicioActividades',
                'informacion_fechas_contribuyente.fecha_inicio_actividades',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'ceased_activities_at',
            $payloadMapper->extractStringValue($data, [
                'ceased_activities_at',
                'informacionFechasContribuyente.fechaCese',
                'informacion_fechas_contribuyente.fecha_cese',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'restarted_activities_at',
            $payloadMapper->extractStringValue($data, [
                'restarted_activities_at',
                'informacionFechasContribuyente.fechaReinicioActividades',
                'informacion_fechas_contribuyente.fecha_reinicio_actividades',
            ])
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'sri_updated_at',
            $payloadMapper->extractStringValue($data, [
                'sri_updated_at',
                'informacionFechasContribuyente.fechaActualizacion',
                'informacion_fechas_contribuyente.fecha_actualizacion',
            ])
        ) || $hasPopulatedFields;

        $legalRepresentatives = $this->mapLegalRepresentatives($data);

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'legal_representatives',
            $legalRepresentatives
        ) || $hasPopulatedFields;

        $hasPopulatedFields = $this->setWhenNotNull(
            $set,
            'suspension_cancellation_reason',
            $payloadMapper->extractStringValue($data, [
                'suspension_cancellation_reason',
                'motivoCancelacionSuspension',
                'motivo_cancelacion_suspension',
            ])
        ) || $hasPopulatedFields;

        if (! $withEstablishments || $get === null || ! $this->canSetSriField('establishments') || $this->hasManualEstablishments($get('establishments'))) {
            return $hasPopulatedFields;
        }

        $mappedEstablishments = $payloadMapper->mapEstablishments(
            $this->consultarEstablecimientosPorRuc($ruc)
        );

        if ($mappedEstablishments === []) {
            return $hasPopulatedFields;
        }

        $set('establishments', $mappedEstablishments);

        return true;
    }

    private function setWhenNotNull(callable $set, string $field, mixed $value): bool
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

    private function hasManualEstablishments(mixed $establishments): bool
    {
        if (! is_array($establishments)) {
            return false;
        }

        return collect($establishments)->contains(function (mixed $establishment): bool {
            if (! is_array($establishment)) {
                return false;
            }

            $establishmentCode = mb_trim((string) ($establishment['establishment_code'] ?? ''));
            $emissionPointCode = mb_trim((string) ($establishment['emission_point_code'] ?? ''));

            return $establishmentCode !== '' || $emissionPointCode !== '';
        });
    }

    private function consultarContribuyente(string $ruc): array
    {
        /** @var SriServiceInterface $sriService */
        $sriService = app(SriServiceInterface::class);

        return $sriService->consultarContribuyente($ruc);
    }

    private function consultarEstablecimientosPorRuc(string $ruc): array
    {
        /** @var SriServiceInterface $sriService */
        $sriService = app(SriServiceInterface::class);

        return $sriService->consultarEstablecimientosPorRuc($ruc);
    }

    private function normalizeRuc(?string $value): string
    {
        return preg_replace('/\D/', '', $value ?? '') ?? '';
    }

    private function hasValidRuc(string $ruc): bool
    {
        return mb_strlen($ruc) === 13;
    }

    private function sriPayloadMapper(): SriPayloadMapper
    {
        return app(SriPayloadMapper::class);
    }
}
