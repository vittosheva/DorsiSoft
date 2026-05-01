<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Mappers;

use Modules\Core\Models\Company;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Models\PurchaseSettlementItem;
use Modules\Sri\DTOs\PurchaseSettlementXmlData;
use Modules\Sri\DTOs\SriAdditionalInfoData;
use Modules\Sri\DTOs\SriInvoiceItemData;
use Modules\Sri\DTOs\SriPaymentData;
use Modules\Sri\DTOs\SriRecipientData;
use Modules\Sri\DTOs\SriTaxLineData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsIssuerData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsRecipientData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsTaxData;

final class PurchaseSettlementXmlMapper
{
    use BuildsIssuerData;
    use BuildsRecipientData;
    use BuildsTaxData;

    public function map(PurchaseSettlement $settlement, Company $company): PurchaseSettlementXmlData
    {
        $issuer = $this->buildIssuerData($settlement, $company);

        $recipient = new SriRecipientData(
            tipoIdentificacion: $this->toSriIdentificationTypeCode($settlement->supplier_identification_type, '05'),
            identificacion: $settlement->supplier_identification ?? '',
            razonSocial: $settlement->supplier_name ?? '',
            direccion: $settlement->supplier_address,
        );

        $detalles = $settlement->items->map(fn (PurchaseSettlementItem $item) => $this->mapItem($item))->all();
        $totalesConImpuestos = $this->buildTotalesFromItems($settlement);
        $pagos = $this->mapPayments($settlement->sri_payments ?? []);
        $infoAdicional = $this->mapAdditionalInfo($settlement->additional_info ?? []);

        return new PurchaseSettlementXmlData(
            issuer: $issuer,
            recipient: $recipient,
            fechaEmision: $settlement->issue_date?->format('d/m/Y') ?? now()->format('d/m/Y'),
            totalSinImpuestos: number_format((float) $settlement->subtotal, 2, '.', ''),
            totalDescuento: '0.00',
            importeTotal: number_format((float) $settlement->total, 2, '.', ''),
            moneda: 'DOLAR',
            totalesConImpuestos: $totalesConImpuestos,
            detalles: $detalles,
            pagos: $pagos,
            infoAdicional: $infoAdicional,
            obligadoContabilidad: (bool) $company->is_accounting_required,
        );
    }

    private function mapItem(PurchaseSettlementItem $item): SriInvoiceItemData
    {
        // PurchaseSettlementItems don't have individual tax breakdown — build from tax_amount
        $impuestos = $this->buildItemTax($item);

        return new SriInvoiceItemData(
            codigoPrincipal: $item->product_code ?? 'SIN-CODIGO',
            codigoAuxiliar: null,
            descripcion: $item->product_name ?? $item->description ?? '',
            cantidad: number_format((float) $item->quantity, 6, '.', ''),
            precioUnitario: number_format((float) $item->unit_price, 6, '.', ''),
            descuento: number_format((float) $item->discount_amount, 2, '.', ''),
            precioTotalSinImpuesto: number_format((float) $item->subtotal, 2, '.', ''),
            impuestos: $impuestos,
        );
    }

    /**
     * Build a simplified tax line for a purchase settlement item based on its tax_amount.
     * Purchase settlements typically apply IVA 15%.
     *
     * @return list<SriTaxLineData>
     */
    private function buildItemTax(PurchaseSettlementItem $item): array
    {
        if ((float) $item->tax_amount === 0.0) {
            return [
                new SriTaxLineData(
                    codigo: '2',
                    codigoPorcentaje: '0',
                    tarifa: '0.00',
                    baseImponible: number_format((float) $item->subtotal, 2, '.', ''),
                    valor: '0.00',
                ),
            ];
        }

        // Infer rate from subtotal and tax_amount
        $subtotal = (float) $item->subtotal;
        $taxAmount = (float) $item->tax_amount;
        $rate = $subtotal > 0 ? round(($taxAmount / $subtotal) * 100, 0) : 15;

        return [
            new SriTaxLineData(
                codigo: '2',
                codigoPorcentaje: $this->ivaRateToCodigoPorcentaje('IVA', (float) $rate),
                tarifa: number_format($rate, 2, '.', ''),
                baseImponible: number_format($subtotal, 2, '.', ''),
                valor: number_format($taxAmount, 2, '.', ''),
            ),
        ];
    }

    /**
     * Build document-level tax totals from settlement totals.
     *
     * @return list<SriTaxLineData>
     */
    private function buildTotalesFromItems(PurchaseSettlement $settlement): array
    {
        $subtotal = (float) $settlement->tax_base;
        $taxAmount = (float) $settlement->tax_amount;

        if ($taxAmount === 0.0) {
            return [
                new SriTaxLineData(
                    codigo: '2',
                    codigoPorcentaje: '0',
                    tarifa: '0.00',
                    baseImponible: number_format($subtotal, 2, '.', ''),
                    valor: '0.00',
                ),
            ];
        }

        $rate = $subtotal > 0 ? round(($taxAmount / $subtotal) * 100, 0) : 15;

        return [
            new SriTaxLineData(
                codigo: '2',
                codigoPorcentaje: $this->ivaRateToCodigoPorcentaje('IVA', (float) $rate),
                tarifa: number_format($rate, 2, '.', ''),
                baseImponible: number_format($subtotal, 2, '.', ''),
                valor: number_format($taxAmount, 2, '.', ''),
            ),
        ];
    }

    /**
     * @param  array<array{method: string, amount: string}>  $sriPayments
     * @return list<SriPaymentData>
     */
    private function mapPayments(array $sriPayments): array
    {
        if (empty($sriPayments)) {
            return [];
        }

        return array_map(
            fn (array $p) => new SriPaymentData(
                formaPago: $p['method'],
                total: number_format((float) $p['amount'], 2, '.', ''),
            ),
            $sriPayments,
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
}
