<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Mappers;

use Modules\Core\Models\Company;
use Modules\Sales\Models\Withholding;
use Modules\Sales\Models\WithholdingItem;
use Modules\Sri\DTOs\SriAdditionalInfoData;
use Modules\Sri\DTOs\SriRecipientData;
use Modules\Sri\DTOs\SriWithholdingItemData;
use Modules\Sri\DTOs\WithholdingXmlData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsIssuerData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsRecipientData;

final class WithholdingXmlMapper
{
    use BuildsIssuerData;
    use BuildsRecipientData;

    public function map(Withholding $withholding, Company $company): WithholdingXmlData
    {
        $issuer = $this->buildIssuerData($withholding, $company);

        $recipient = new SriRecipientData(
            tipoIdentificacion: $this->toSriIdentificationTypeCode($withholding->supplier_identification_type, '04'),
            identificacion: $withholding->supplier_identification ?? '',
            razonSocial: $withholding->supplier_name ?? '',
            direccion: $withholding->supplier_address,
        );

        $periodoFiscal = $this->formatPeriodFiscal($withholding->period_fiscal);
        $impuestos = $withholding->items->map(fn (WithholdingItem $item) => $this->mapItem($item))->all();
        $infoAdicional = $this->mapAdditionalInfo($withholding->additional_info ?? []);

        return new WithholdingXmlData(
            issuer: $issuer,
            recipient: $recipient,
            fechaEmision: $withholding->issue_date?->format('d/m/Y') ?? now()->format('d/m/Y'),
            periodoFiscal: $periodoFiscal,
            obligadoContabilidad: (bool) $company->is_accounting_required,
            impuestos: $impuestos,
            infoAdicional: $infoAdicional,
        );
    }

    private function mapItem(WithholdingItem $item): SriWithholdingItemData
    {
        // tax_type: '1' = IR (Impuesto a la Renta), '2' = IVA
        $codigo = match (mb_strtoupper($item->tax_type ?? 'IR')) {
            'IVA', '2' => '2',
            default => '1', // IR
        };

        // Format source document number to match SRI XSD pattern: 15 digits (no separators)
        $rawNum = (string) ($item->source_document_number ?? '');
        $num = preg_replace('/\D+/', '', $rawNum);
        if ($num !== '') {
            $num = mb_str_pad(mb_substr($num, 0, 15), 15, '0', STR_PAD_LEFT);
        }

        return new SriWithholdingItemData(
            codigo: $codigo,
            codigoRetencion: $item->tax_code ?? '',
            baseImponible: number_format((float) $item->base_amount, 2, '.', ''),
            porcentajeRetener: number_format((float) $item->tax_rate, 2, '.', ''),
            valorRetenido: number_format((float) $item->withheld_amount, 2, '.', ''),
            codDocSustento: $item->source_document_type ?? '01',
            numDocSustento: $num,
            fechaEmisionDocSustento: $item->source_document_date?->format('d/m/Y') ?? now()->format('d/m/Y'),
        );
    }

    /**
     * Formats period_fiscal from 'YYYY/MM' or 'YYYY-MM' to 'MM/YYYY'.
     */
    private function formatPeriodFiscal(?string $periodFiscal): string
    {
        if (blank($periodFiscal)) {
            return now()->format('m/Y');
        }

        // Accept both 'YYYY/MM' and 'MM/YYYY'
        if (preg_match('/^(\d{4})[\/\-](\d{2})$/', $periodFiscal, $m)) {
            return "{$m[2]}/{$m[1]}";
        }

        return $periodFiscal;
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
}
