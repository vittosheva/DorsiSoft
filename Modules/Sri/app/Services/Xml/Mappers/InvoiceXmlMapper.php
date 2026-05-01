<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Mappers;

use Modules\Core\Models\Company;
use Modules\Finance\Models\Tax;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceItem;
use Modules\Sales\Models\InvoiceItemTax;
use Modules\Sri\DTOs\InvoiceXmlData;
use Modules\Sri\DTOs\SriAdditionalInfoData;
use Modules\Sri\DTOs\SriInvoiceItemData;
use Modules\Sri\DTOs\SriPaymentData;
use Modules\Sri\DTOs\SriRecipientData;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsIssuerData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsRecipientData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsTaxData;
use Modules\System\Enums\TaxCalculationTypeEnum;

final class InvoiceXmlMapper
{
    use BuildsIssuerData;
    use BuildsRecipientData;
    use BuildsTaxData;

    public function map(Invoice $invoice, Company $company): InvoiceXmlData
    {
        $issuer = $this->buildIssuerData($invoice, $company);

        $recipient = new SriRecipientData(
            tipoIdentificacion: $this->toSriIdentificationTypeCode($invoice->customer_identification_type),
            identificacion: $invoice->customer_identification ?? '9999999999999',
            razonSocial: $invoice->customer_name ?? 'CONSUMIDOR FINAL',
            direccion: $invoice->customer_address,
            email: $this->extractFirstEmail($invoice->customer_email),
            telefono: $invoice->customer_phone,
        );

        $company->loadMissing('defaultTax');
        $defaultTax = $company->defaultTax;

        $docTypeCode = $invoice->getSriDocumentTypeCode();
        $detalles = $invoice->items->map(fn (InvoiceItem $item) => $this->mapItem($item, $docTypeCode, $defaultTax))->all();

        $allTaxes = $invoice->items->flatMap(fn (InvoiceItem $item) => $item->taxes->isNotEmpty() ? $this->sortTaxes($item->taxes, $docTypeCode) : collect([$this->buildDefaultTaxEntry($defaultTax, $item)]));
        $totalesConImpuestos = $this->aggregateTaxLines($allTaxes, $docTypeCode);

        $pagos = $this->mapPayments(
            $invoice->sri_payments ?? [],
            number_format((float) $invoice->total, 2, '.', '')
        );
        $infoAdicional = $this->mapAdditionalInfo($invoice->additional_info ?? []);

        return new InvoiceXmlData(
            issuer: $issuer,
            recipient: $recipient,
            fechaEmision: $invoice->issue_date?->format('d/m/Y') ?? now()->format('d/m/Y'),
            totalSinImpuestos: number_format((float) $invoice->subtotal, 2, '.', ''),
            totalDescuento: number_format((float) $invoice->discount_amount, 2, '.', ''),
            importeTotal: number_format((float) $invoice->total, 2, '.', ''),
            moneda: 'DOLAR',
            propina: '0.00',
            totalesConImpuestos: $totalesConImpuestos,
            detalles: $detalles,
            pagos: $pagos,
            infoAdicional: $infoAdicional,
            obligadoContabilidad: (bool) $company->is_accounting_required,
        );
    }

    private function mapItem(InvoiceItem $item, string $documentTypeCode = '', ?Tax $defaultTax = null): SriInvoiceItemData
    {
        $taxes = $item->taxes;

        if ($taxes->isEmpty()) {
            if ($defaultTax === null) {
                throw new XmlGenerationException(
                    "El ítem '{$item->product_name}' no tiene impuestos asignados y la empresa no tiene un impuesto por defecto configurado. "
                        .'Configure el impuesto por defecto en el perfil de la empresa.'
                );
            }

            $taxes = collect([$this->buildDefaultTaxEntry($defaultTax, $item)]);
        }

        $taxes = $this->sortTaxes($taxes, $documentTypeCode);
        $this->ensureUniqueTaxCodes($taxes, $documentTypeCode, $item->product_name ?? $item->description);

        $impuestos = $taxes->map(fn ($tax) => $this->mapItemTax($tax, $documentTypeCode))->all();

        return new SriInvoiceItemData(
            codigoPrincipal: $item->product_code ?? 'SIN-CODIGO',
            codigoAuxiliar: null,
            descripcion: $item->product_name ?? $item->description ?? '',
            cantidad: number_format((float) $item->quantity, 6, '.', ''),
            precioUnitario: number_format((float) $item->unit_price, 6, '.', ''),
            descuento: number_format((float) $item->discount_amount, 2, '.', ''),
            precioTotalSinImpuesto: number_format((float) $item->subtotal, 2, '.', ''),
            impuestos: $impuestos,
            detalle1: $item->detail_1 ?? null,
            detalle2: $item->detail_2 ?? null,
        );
    }

    private function buildDefaultTaxEntry(Tax $defaultTax, InvoiceItem $item): InvoiceItemTax
    {
        $baseAmount = (float) $item->subtotal;
        $rate = (float) $defaultTax->rate;
        $calculationType = $defaultTax->calculation_type instanceof TaxCalculationTypeEnum
            ? $defaultTax->calculation_type
            : TaxCalculationTypeEnum::tryFrom((string) $defaultTax->calculation_type);

        $entry = new InvoiceItemTax();
        $entry->tax_type = $defaultTax->type->value;
        $entry->tax_code = $defaultTax->sri_code;
        $entry->tax_percentage_code = $defaultTax->sri_percentage_code;
        $entry->tax_rate = $rate;
        $entry->tax_calculation_type = $calculationType?->value ?? TaxCalculationTypeEnum::Percentage->value;
        $entry->base_amount = $baseAmount;
        $entry->tax_amount = ($calculationType === TaxCalculationTypeEnum::Fixed)
            ? round((float) $item->quantity * $rate, 4)
            : round($baseAmount * ($rate / 100), 4);

        return $entry;
    }

    /**
     * @param  array<array{method: string, amount: string}>  $sriPayments
     * @return list<SriPaymentData>
     */
    private function mapPayments(array $sriPayments, string $fallbackTotal): array
    {
        if (empty($sriPayments)) {
            return [new SriPaymentData(formaPago: '01', total: $fallbackTotal)];
        }

        return array_map(
            fn (array $p) => new SriPaymentData(
                formaPago: $p['method'],
                total: number_format((float) $p['amount'], 2, '.', ''),
            ),
            $sriPayments,
        );
    }

    /**
     * @param  array<string, string>|array<array{name: string, value: string}>  $additionalInfo
     * @return list<SriAdditionalInfoData>
     */
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
