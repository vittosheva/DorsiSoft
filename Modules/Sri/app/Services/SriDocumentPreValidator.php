<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\Company;
use Modules\Sales\Models\DeliveryGuide;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Support\ItemTaxTypeGuard;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\SriEnvironmentEnum;
use Modules\Sri\Exceptions\XmlGenerationException;

/**
 * Validates SRI document data before XML generation.
 *
 * Runs synchronously in Filament actions to provide immediate user feedback,
 * and also in the orchestrator as a defence-in-depth layer.
 */
final class SriDocumentPreValidator
{
    /**
     * @throws XmlGenerationException With a human-readable message identifying the failing field
     */
    public function validate(HasElectronicBilling $document, Company $company): void
    {
        $this->validateCompany($document, $company);
        $this->validateDocument($document);
        $this->validateDocumentSpecific($document, $company);
    }

    /**
     * @throws XmlGenerationException
     */
    private function validateCompany(HasElectronicBilling $document, Company $company): void
    {
        /** @var Model&HasElectronicBilling $document */
        $prefix = "Documento [{$document->id}]:";

        if (blank($company->ruc) || ! preg_match('/^\d{13}$/', (string) $company->ruc)) {
            throw new XmlGenerationException(
                "{$prefix} El RUC de la empresa '{$company->ruc}' no es válido. Debe tener exactamente 13 dígitos."
            );
        }

        if (blank($company->tax_address)) {
            throw new XmlGenerationException(
                "{$prefix} La empresa no tiene dirección fiscal (Dirección Matriz). Configure la dirección fiscal antes de emitir comprobantes."
            );
        }

        if (blank($company->legal_name)) {
            throw new XmlGenerationException(
                "{$prefix} La empresa no tiene razón social. Configure la razón social antes de emitir comprobantes."
            );
        }

        if (! ($company->sri_environment instanceof SriEnvironmentEnum)) {
            throw new XmlGenerationException(
                "{$prefix} No se ha configurado el ambiente SRI (pruebas/producción) para la empresa."
            );
        }

        if (blank($company->certificate_path) || blank($company->certificate_password_encrypted)) {
            throw new XmlGenerationException(
                "{$prefix} La empresa no tiene un certificado digital configurado. "
                    .'Suba el certificado (.p12 / .pfx) y su contraseña en el perfil de la empresa antes de emitir comprobantes electrónicos.'
            );
        }
    }

    /**
     * @throws XmlGenerationException
     */
    private function validateDocument(HasElectronicBilling $document): void
    {
        /** @var Model&HasElectronicBilling $document */
        $prefix = "Documento [{$document->id}]:";

        if (! preg_match('/^\d{3}$/', (string) $document->establishment_code)) {
            throw new XmlGenerationException(
                "{$prefix} El código de establecimiento '{$document->establishment_code}' no es válido. Debe tener exactamente 3 dígitos (ej. 001)."
            );
        }

        if (! preg_match('/^\d{3}$/', (string) $document->emission_point_code)) {
            throw new XmlGenerationException(
                "{$prefix} El código de punto de emisión '{$document->emission_point_code}' no es válido. Debe tener exactamente 3 dígitos (ej. 001)."
            );
        }

        if (! preg_match('/^\d{9}$/', (string) $document->sequential_number)) {
            throw new XmlGenerationException(
                "{$prefix} El número secuencial '{$document->sequential_number}' no es válido. Debe tener exactamente 9 dígitos (ej. 000000001)."
            );
        }

        $hasDate = (isset($document->issue_date) && $document->issue_date !== null)
            || (isset($document->transport_date) && $document->transport_date !== null)
            || (isset($document->transport_start_date) && $document->transport_start_date !== null)
            || (isset($document->transport_end_date) && $document->transport_end_date !== null);

        if (! $hasDate) {
            throw new XmlGenerationException(
                "{$prefix} El documento no tiene fecha de emisión. Establezca la fecha antes de procesar."
            );
        }
    }

    /**
     * @throws XmlGenerationException
     */
    private function validateDocumentSpecific(HasElectronicBilling $document, Company $company): void
    {
        if ($document instanceof Invoice) {
            $this->validateInvoice($document, $company);

            return;
        }

        if ($document instanceof DeliveryGuide) {
            $this->validateDeliveryGuide($document);
        }
    }

    /**
     * @throws XmlGenerationException
     */
    private function validateInvoice(Invoice $invoice, Company $company): void
    {
        $prefix = "Factura [{$invoice->id}]:";

        $items = $invoice->relationLoaded('items') ? $invoice->items : collect();

        if ($items->isEmpty()) {
            throw new XmlGenerationException(
                "{$prefix} La factura no tiene ítems. Agregue al menos un producto antes de procesar."
            );
        }

        $hasDefaultTax = filled($company->default_tax_id);

        foreach ($items as $item) {
            $taxes = $item->relationLoaded('taxes') ? $item->taxes : collect();

            if ($taxes->isEmpty() && ! $hasDefaultTax) {
                throw new XmlGenerationException(
                    "{$prefix} El ítem '{$item->product_name}' no tiene impuestos asignados y la empresa no tiene un impuesto por defecto configurado. "
                        .'Asigne impuestos al ítem o configure el impuesto por defecto en el perfil de la empresa.'
                );
            }

            $duplicateTypes = app(ItemTaxTypeGuard::class)->duplicateTypes($taxes);

            if ($duplicateTypes !== []) {
                throw new XmlGenerationException(
                    "{$prefix} El ítem '{$item->product_name}' repite tipos de impuesto no permitidos: "
                        .implode(', ', $duplicateTypes)
                        .'. Solo se permite un impuesto por tipo en cada ítem.'
                );
            }
        }
    }

    /**
     * @throws XmlGenerationException
     */
    private function validateDeliveryGuide(DeliveryGuide $deliveryGuide): void
    {
        $prefix = "Guía de remisión [{$deliveryGuide->id}]:";

        $deliveryGuide->loadMissing('carrier.carrierVehicles', 'recipients.items');

        $carrierName = $deliveryGuide->carrier_name ?: $deliveryGuide->carrier?->legal_name;
        $carrierIdentification = $deliveryGuide->carrier_identification ?: $deliveryGuide->carrier?->identification_number;
        $carrierPlate = $deliveryGuide->carrier_plate ?: $deliveryGuide->carrier?->carrierVehicles->first()?->vehicle_plate;

        if (blank($carrierName)) {
            throw new XmlGenerationException("{$prefix} Falta la razón social del transportista.");
        }

        if (blank($carrierIdentification)) {
            throw new XmlGenerationException("{$prefix} Falta la identificación (RUC/CI) del transportista.");
        }

        if (blank($carrierPlate)) {
            throw new XmlGenerationException("{$prefix} Falta la placa del transportista.");
        }

        $primaryRecipient = $deliveryGuide->recipients->first();

        if (! $primaryRecipient) {
            throw new XmlGenerationException("{$prefix} Agregue al menos un destinatario antes de emitir.");
        }

        if (blank($primaryRecipient->recipient_name)) {
            throw new XmlGenerationException("{$prefix} Falta la razón social del destinatario principal.");
        }

        if (blank($primaryRecipient->recipient_identification)) {
            throw new XmlGenerationException("{$prefix} Falta la identificación del destinatario principal.");
        }

        if (blank($primaryRecipient->destination_address)) {
            throw new XmlGenerationException("{$prefix} Falta la dirección del destinatario principal.");
        }

        if (blank($primaryRecipient->transfer_reason)) {
            throw new XmlGenerationException("{$prefix} Falta el motivo de traslado del destinatario principal.");
        }

        if ($primaryRecipient->items->isEmpty()) {
            throw new XmlGenerationException("{$prefix} El destinatario principal no tiene ítems de traslado.");
        }
    }
}
