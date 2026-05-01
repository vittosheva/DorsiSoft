<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Mappers;

use Modules\Core\Models\Company;
use Modules\Sales\Models\DebitNote;
use Modules\Sri\DTOs\DebitNoteReason;
use Modules\Sri\DTOs\DebitNoteXmlData;
use Modules\Sri\DTOs\SriAdditionalInfoData;
use Modules\Sri\DTOs\SriPaymentData;
use Modules\Sri\DTOs\SriRecipientData;
use Modules\Sri\DTOs\SriTaxLineData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsIssuerData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsRecipientData;

final class DebitNoteXmlMapper
{
    use BuildsIssuerData;
    use BuildsRecipientData;

    public function map(DebitNote $debitNote, Company $company): DebitNoteXmlData
    {
        $issuer = $this->buildIssuerData($debitNote, $company);

        $recipient = new SriRecipientData(
            tipoIdentificacion: $this->toSriIdentificationTypeCode($debitNote->customer_identification_type),
            identificacion: $debitNote->customer_identification ?? '9999999999999',
            razonSocial: $debitNote->customer_name ?? 'CONSUMIDOR FINAL',
            direccion: $debitNote->customer_address,
            email: $this->extractFirstEmail($debitNote->customer_email),
            telefono: $debitNote->customer_phone,
        );

        // Source invoice references
        $invoice = $debitNote->relationLoaded('invoice') ? $debitNote->invoice : null;
        $codDocSustento = '01';
        $numDocSustento = $invoice
            ? "{$invoice->establishment_code}-{$invoice->emission_point_code}-{$invoice->sequential_number}"
            : ($debitNote->ext_invoice_code ?? '001-001-000000001');
        $fechaEmisionDocSustento = $invoice
            ? $invoice->issue_date?->format('d/m/Y')
            : ($debitNote->ext_invoice_date?->format('d/m/Y') ?? now()->format('d/m/Y'));

        $reasons = $this->mapReasons($debitNote->reasons ?? []);
        $impuestos = $this->buildImpuestos($debitNote);
        $pagos = $this->mapPayments($debitNote);
        $infoAdicional = $this->mapAdditionalInfo($debitNote->additional_info ?? []);

        return new DebitNoteXmlData(
            issuer: $issuer,
            recipient: $recipient,
            fechaEmision: $debitNote->issue_date?->format('d/m/Y') ?? now()->format('d/m/Y'),
            fechaEmisionDocSustento: $fechaEmisionDocSustento ?? now()->format('d/m/Y'),
            numDocSustento: $numDocSustento,
            codDocSustento: $codDocSustento,
            totalSinImpuestos: number_format((float) $debitNote->subtotal, 2, '.', ''),
            importeTotal: number_format((float) $debitNote->total, 2, '.', ''),
            moneda: 'DOLAR',
            reasons: $reasons,
            impuestos: $impuestos,
            pagos: $pagos,
            infoAdicional: $infoAdicional,
        );
    }

    /**
     * @param  array<array{reason?: string, value?: float|int|string}>  $reasons
     * @return list<DebitNoteReason>
     */
    private function mapReasons(array $reasons): array
    {
        return array_map(
            fn (array $reason) => new DebitNoteReason(
                reason: $reason['reason'] ?? '',
                value: number_format((float) ($reason['value'] ?? 0), 2, '.', ''),
            ),
            $reasons,
        );
    }

    /** @return list<SriTaxLineData> */
    private function buildImpuestos(DebitNote $debitNote): array
    {
        $rate = (float) $debitNote->tax_rate;

        return [
            new SriTaxLineData(
                codigo: '2',
                codigoPorcentaje: match (true) {
                    $rate === 0.0 => '0',
                    $rate === 5.0 => '6',
                    $rate === 12.0 => '2',
                    $rate === 14.0 => '3',
                    $rate === 15.0 => '4',
                    default => '2',
                },
                tarifa: number_format($rate, 2, '.', ''),
                baseImponible: number_format((float) $debitNote->subtotal, 2, '.', ''),
                valor: number_format((float) $debitNote->tax_amount, 2, '.', ''),
            ),
        ];
    }

    /** @return list<SriPaymentData> */
    private function mapPayments(DebitNote $debitNote): array
    {
        $payments = $debitNote->getResolvedSriPayments();

        if ($payments === []) {
            return [];
        }

        return array_map(
            static fn (array $payment): SriPaymentData => new SriPaymentData(
                formaPago: $payment['method'],
                total: number_format((float) ($payment['amount'] ?? 0), 2, '.', ''),
            ),
            $payments,
        );
    }

    /** @return list<SriAdditionalInfoData> */
    private function mapAdditionalInfo(array $additionalInfo): array
    {
        $result = [];

        foreach ($additionalInfo as $key => $value) {
            if (is_array($value)) {
                $result[] = new SriAdditionalInfoData(
                    nombre: $value['name'] ?? (string) $key,
                    valor: $value['value'] ?? '',
                );
            } else {
                $result[] = new SriAdditionalInfoData(
                    nombre: (string) $key,
                    valor: (string) $value,
                );
            }
        }

        return $result;
    }

    private function extractFirstEmail(mixed $customerEmail): ?string
    {
        if (is_null($customerEmail)) {
            return null;
        }

        if (is_array($customerEmail)) {
            return $customerEmail[0] ?? null;
        }

        return (string) $customerEmail;
    }
}
