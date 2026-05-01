<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Models\DocumentSequence;
use Modules\Sri\Models\DocumentSequenceHistory;

/**
 * Gestión de secuenciales SRI por (empresa, establecimiento, punto de emisión, tipo de documento).
 *
 * Estrategia "mixto":
 *   - suggest()     → lectura no bloqueante del siguiente sugerido (para pre-rellenar formularios)
 *   - isAvailable() → valida que el secuencial no esté ya usado en la tabla del documento
 *   - record()      → registra el secuencial emitido con SELECT FOR UPDATE; el caller debe estar
 *                     dentro de un DB::transaction()
 *   - reset()       → permite configurar el siguiente número manualmente (migración de ERP),
 *                     con auditoría obligatoria
 *   - history()     → consulta el historial de cambios de una secuencia
 *
 * La unicidad final está garantizada por UNIQUE constraints en cada tabla de documento.
 */
final class DocumentSequentialService
{
    private const PADDING = 9;

    /**
     * Sugiere el siguiente secuencial disponible (lectura no bloqueante).
     * Devuelve el número formateado como string de 9 dígitos (ej: "000000042").
     */
    public function suggest(
        int $companyId,
        string $establishmentCode,
        string $emissionPointCode,
        SriDocumentTypeEnum $documentType,
    ): string {
        $lastRecorded = DocumentSequence::query()
            ->select('last_sequential')
            ->where('company_id', $companyId)
            ->where('establishment_code', $establishmentCode)
            ->where('emission_point_code', $emissionPointCode)
            ->where('document_type', $documentType->value)
            ->value('last_sequential') ?? 0;

        // Siempre verificar el máximo real en la tabla del documento (incluyendo drafts),
        // para evitar sugerir un secuencial que ya esté en uso aunque last_sequential esté desactualizado.
        $table = $this->resolveTable($documentType);

        $maxInTable = DB::table($table)
            ->where('company_id', $companyId)
            ->where('establishment_code', $establishmentCode)
            ->where('emission_point_code', $emissionPointCode)
            ->whereNotNull('sequential_number')
            ->whereNull('deleted_at')
            ->orderByDesc('sequential_number')
            ->limit(1)
            ->value('sequential_number');

        $last = max($lastRecorded, filled($maxInTable) ? (int) $maxInTable : 0);

        return mb_str_pad((string) ($last + 1), self::PADDING, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica que el secuencial no esté ya usado en el documento correspondiente.
     */
    public function isAvailable(
        int $companyId,
        string $establishmentCode,
        string $emissionPointCode,
        string $sequential,
        SriDocumentTypeEnum $documentType,
        ?int $excludeId = null,
    ): bool {
        $table = $this->resolveTable($documentType);

        $query = DB::table($table)
            ->where('company_id', $companyId)
            ->where('establishment_code', $establishmentCode)
            ->where('emission_point_code', $emissionPointCode)
            ->where('sequential_number', $sequential)
            ->whereNull('deleted_at');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Registra el secuencial emitido con bloqueo pesimista.
     *
     * El caller DEBE estar dentro de un DB::transaction(). Este método no abre
     * su propia transacción para ser composable con otras operaciones del documento.
     *
     * Solo actualiza last_sequential si el nuevo valor es mayor al registrado,
     * evitando retrocesos ante registros fuera de orden.
     */
    public function record(
        int $companyId,
        string $establishmentCode,
        string $emissionPointCode,
        string $sequential,
        SriDocumentTypeEnum $documentType,
        ?int $performedBy = null,
    ): void {
        $sequentialInt = (int) $sequential;

        // Upsert sin lock — idempotente, la fila puede crearse aquí si no existe
        $sequence = DocumentSequence::firstOrCreate(
            [
                'company_id' => $companyId,
                'establishment_code' => $establishmentCode,
                'emission_point_code' => $emissionPointCode,
                'document_type' => $documentType->value,
            ],
            ['last_sequential' => 0]
        );

        // Lock sobre la fila para la duración de la transacción del caller
        $sequence = DocumentSequence::where('id', $sequence->id)->lockForUpdate()->first();

        $previous = $sequence->last_sequential;

        if ($sequentialInt > $sequence->last_sequential) {
            $sequence->last_sequential = $sequentialInt;
            $sequence->save();
        }

        $this->writeHistory($sequence, 'record', $previous, $sequentialInt, null, $performedBy);
    }

    /**
     * Configura manualmente el siguiente número de secuencial.
     *
     * Usado al migrar desde otro ERP para definir desde qué número continúa la emisión.
     * El próximo suggest() devolverá exactamente $newStart.
     * Requiere un motivo para auditoría.
     *
     * @throws InvalidArgumentException Si $newStart < 1
     */
    public function reset(
        int $companyId,
        string $establishmentCode,
        string $emissionPointCode,
        SriDocumentTypeEnum $documentType,
        int $newStart,
        string $reason,
        ?int $performedBy = null,
    ): void {
        if ($newStart < 1) {
            throw new InvalidArgumentException(__('newStart must be greater than or equal to 1.'));
        }

        DB::transaction(function () use ($companyId, $establishmentCode, $emissionPointCode, $documentType, $newStart, $reason, $performedBy): void {
            $sequence = DocumentSequence::where('company_id', $companyId)
                ->where('establishment_code', $establishmentCode)
                ->where('emission_point_code', $emissionPointCode)
                ->where('document_type', $documentType->value)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = DocumentSequence::create([
                    'company_id' => $companyId,
                    'establishment_code' => $establishmentCode,
                    'emission_point_code' => $emissionPointCode,
                    'document_type' => $documentType->value,
                    'last_sequential' => 0,
                ]);
            }

            $previous = $sequence->last_sequential;
            $sequence->last_sequential = $newStart - 1;
            $sequence->save();

            $this->writeHistory($sequence, 'reset', $previous, $newStart - 1, $reason, $performedBy);
        });
    }

    /**
     * Devuelve el historial de cambios de una secuencia, ordenado del más reciente.
     *
     * @return Collection<int, DocumentSequenceHistory>
     */
    public function history(
        int $companyId,
        string $establishmentCode,
        string $emissionPointCode,
        SriDocumentTypeEnum $documentType,
        int $limit = 50,
    ): Collection {
        return DocumentSequenceHistory::where('company_id', $companyId)
            ->where('establishment_code', $establishmentCode)
            ->where('emission_point_code', $emissionPointCode)
            ->where('document_type', $documentType->value)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function writeHistory(
        DocumentSequence $sequence,
        string $event,
        int $previousValue,
        int $newValue,
        ?string $reason,
        ?int $performedBy,
    ): void {
        DocumentSequenceHistory::create([
            'document_sequence_id' => $sequence->id,
            'company_id' => $sequence->company_id,
            'establishment_code' => $sequence->establishment_code,
            'emission_point_code' => $sequence->emission_point_code,
            'document_type' => $sequence->document_type,
            'event' => $event,
            'previous_value' => $previousValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'performed_by' => $performedBy,
        ]);
    }

    private function resolveTable(SriDocumentTypeEnum $documentType): string
    {
        return match ($documentType) {
            SriDocumentTypeEnum::Invoice => 'sales_invoices',
            SriDocumentTypeEnum::CreditNote => 'sales_credit_notes',
            SriDocumentTypeEnum::DebitNote => 'sales_debit_notes',
            SriDocumentTypeEnum::Withholding => 'sales_withholdings',
            SriDocumentTypeEnum::DeliveryGuide => 'sales_delivery_guides',
            SriDocumentTypeEnum::PurchaseSettlement => 'sales_purchase_settlements',
        };
    }
}
