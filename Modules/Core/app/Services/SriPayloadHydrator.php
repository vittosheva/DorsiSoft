<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Modules\Core\Support\Sri\SriPayloadMapper;
use Modules\Sri\Enums\SriTaxpayerTypeEnum;

final class SriPayloadHydrator
{
    public function __construct(private SriPayloadMapper $payloadMapper) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function hydrate(array $data): array
    {
        $fields = [
            'tax_regime' => $this->payloadMapper->normalizeTaxRegime(
                $this->payloadMapper->extractStringValue($data, ['tax_regime', 'regimen'])
            ),
            'rimpe_expires_at' => $this->payloadMapper->extractStringValue($data, ['rimpe_expires_at', 'fechaFinRimpe', 'fecha_fin_rimpe']),
            'is_accounting_required' => $this->payloadMapper->extractBooleanValue($data, ['is_accounting_required', 'obligadoLlevarContabilidad', 'obligado_llevar_contabilidad']),
            'is_special_taxpayer' => $this->payloadMapper->extractBooleanValue($data, ['is_special_taxpayer', 'contribuyenteEspecial', 'contribuyente_especial']),
            'special_taxpayer_resolution' => $this->payloadMapper->extractStringValue($data, ['special_taxpayer_resolution', 'resolucionContribuyenteEspecial', 'resolucion_contribuyente_especial']),
            'contributor_status' => $this->payloadMapper->extractStringValue($data, ['contributor_status', 'estadoContribuyenteRuc', 'estado_contribuyente_ruc']),
            'taxpayer_type' => SriTaxpayerTypeEnum::normalize(
                $this->payloadMapper->extractStringValue($data, ['taxpayer_type', 'tipoContribuyente', 'tipo_contribuyente'])
            ),
            'contributor_category' => $this->payloadMapper->extractStringValue($data, ['contributor_category', 'categoria']),
            'is_withholding_agent' => $this->payloadMapper->extractBooleanValue($data, ['is_withholding_agent', 'agenteRetencion', 'agente_retencion']),
            'is_shell_company' => $this->payloadMapper->extractBooleanValue($data, ['is_shell_company', 'contribuyenteFantasma', 'contribuyente_fantasma']),
            'has_nonexistent_transactions' => $this->payloadMapper->extractBooleanValue($data, ['has_nonexistent_transactions', 'transaccionesInexistente', 'transacciones_inexistente']),
            'started_activities_at' => $this->payloadMapper->extractStringValue($data, ['started_activities_at', 'informacionFechasContribuyente.fechaInicioActividades', 'informacion_fechas_contribuyente.fecha_inicio_actividades']),
            'ceased_activities_at' => $this->payloadMapper->extractStringValue($data, ['ceased_activities_at', 'informacionFechasContribuyente.fechaCese', 'informacion_fechas_contribuyente.fecha_cese']),
            'restarted_activities_at' => $this->payloadMapper->extractStringValue($data, ['restarted_activities_at', 'informacionFechasContribuyente.fechaReinicioActividades', 'informacion_fechas_contribuyente.fecha_reinicio_actividades']),
            'sri_updated_at' => $this->payloadMapper->extractStringValue($data, ['sri_updated_at', 'informacionFechasContribuyente.fechaActualizacion', 'informacion_fechas_contribuyente.fecha_actualizacion']),
            'suspension_cancellation_reason' => $this->payloadMapper->extractStringValue($data, ['suspension_cancellation_reason', 'motivoCancelacionSuspension', 'motivo_cancelacion_suspension']),
        ];

        return array_filter($fields, fn (mixed $value): bool => $value !== null);
    }
}
