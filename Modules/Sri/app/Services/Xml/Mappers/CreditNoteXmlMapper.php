<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Mappers;

use Illuminate\Support\Collection;
use Modules\Core\Models\Company;
use Modules\Finance\Models\Tax;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\CreditNoteItem;
use Modules\Sales\Models\CreditNoteItemTax;
use Modules\Sri\DTOs\CreditNoteXmlData;
use Modules\Sri\DTOs\SriAdditionalInfoData;
use Modules\Sri\DTOs\SriInvoiceItemData;
use Modules\Sri\DTOs\SriRecipientData;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsIssuerData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsRecipientData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsTaxData;
use Modules\System\Enums\TaxCalculationTypeEnum;

final class CreditNoteXmlMapper
{
    use BuildsIssuerData;
    use BuildsRecipientData;
    use BuildsTaxData;

    public function map(CreditNote $creditNote, Company $company): CreditNoteXmlData
    {
        $issuer = $this->buildIssuerData($creditNote, $company);

        $recipient = new SriRecipientData(
            tipoIdentificacion: $this->toSriIdentificationTypeCode($creditNote->customer_identification_type),
            identificacion: $creditNote->customer_identification ?? '9999999999999',
            razonSocial: $creditNote->customer_name ?? 'CONSUMIDOR FINAL',
            direccion: $creditNote->customer_address,
            email: $this->extractFirstEmail($creditNote->customer_email),
            telefono: $creditNote->customer_phone,
        );

        // Source invoice references
        $invoice = $creditNote->relationLoaded('invoice') ? $creditNote->invoice : null;
        $codDocSustento = '01'; // Factura
        $numDocSustento = $invoice
            ? "{$invoice->establishment_code}-{$invoice->emission_point_code}-{$invoice->sequential_number}"
            : ($creditNote->ext_invoice_code ?? '001-001-000000001');
        $fechaEmisionDocSustento = $invoice
            ? $invoice->issue_date?->format('d/m/Y')
            : ($creditNote->ext_invoice_date?->format('d/m/Y') ?? now()->format('d/m/Y'));

        $company->loadMissing('defaultTax');
        $defaultTax = $company->defaultTax;

        $docTypeCode = $creditNote->getSriDocumentTypeCode();
        $detalles = $creditNote->items->map(fn (CreditNoteItem $item) => $this->mapItem($item, $docTypeCode, $defaultTax))->all();

        $allTaxes = $creditNote->items->flatMap(function (CreditNoteItem $item) use ($defaultTax, $docTypeCode): Collection {
            $taxes = $item->taxes ?? collect();

            return $taxes->isNotEmpty() ? $this->sortTaxes($taxes, $docTypeCode) : collect([$this->buildDefaultTaxEntry($defaultTax, $item)]);
        });
        $totalesConImpuestos = $this->aggregateTaxLines($allTaxes, $docTypeCode);

        $infoAdicional = $this->mapAdditionalInfo($creditNote->additional_info ?? []);

        return new CreditNoteXmlData(
            issuer: $issuer,
            recipient: $recipient,
            fechaEmision: $creditNote->issue_date?->format('d/m/Y') ?? now()->format('d/m/Y'),
            fechaEmisionDocSustento: $fechaEmisionDocSustento ?? now()->format('d/m/Y'),
            numDocSustento: $numDocSustento,
            codDocSustento: $codDocSustento,
            reason: $creditNote->reason ?? '',
            totalSinImpuestos: number_format((float) $creditNote->subtotal, 2, '.', ''),
            valorModificacion: number_format((float) $creditNote->total, 2, '.', ''),
            moneda: 'DOLAR',
            obligadoContabilidad: (bool) $company->is_accounting_required,
            totalesConImpuestos: $totalesConImpuestos,
            detalles: $detalles,
            infoAdicional: $infoAdicional,
        );
    }

    private function mapItem(CreditNoteItem $item, string $documentTypeCode = '', ?Tax $defaultTax = null): SriInvoiceItemData
    {
        $taxes = $item->taxes ?? collect();

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
        );
    }

    private function buildDefaultTaxEntry(Tax $defaultTax, CreditNoteItem $item): CreditNoteItemTax
    {
        $baseAmount = (float) $item->subtotal;
        $rate = (float) $defaultTax->rate;
        $calculationType = $defaultTax->calculation_type instanceof TaxCalculationTypeEnum
            ? $defaultTax->calculation_type
            : TaxCalculationTypeEnum::tryFrom((string) $defaultTax->calculation_type);

        $entry = new CreditNoteItemTax();
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
