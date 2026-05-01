<?php

declare(strict_types=1);

namespace Modules\Sri\Contracts;

/**
 * Contrato para modelos que generan una Representación Impresa de Documento
 * Electrónico (RIDE) en formato PDF v2.
 *
 * Implementado por: Invoice, CreditNote, DebitNote.
 * Extensible a: PurchaseSettlement, DeliveryGuide, Withholding.
 */
interface GeneratesRidePdf
{
    /** Blade view name, e.g. 'sales::pdf.v2.ride-invoice'. */
    public function getRidePdfView(): string;

    /** URL slug used as the {type} route parameter: 'invoice', 'credit-note', 'debit-note'. */
    public function getRidePdfType(): string;

    /**
     * Storage path for the generated RIDE PDF.
     * Convention: tenants/{ruc}/pdfs/v2/ride/{type}/{year}/{access_key}.pdf
     */
    public function getRidePdfStoragePath(string $tenantRuc): string;

    /** Storage disk name. Defaults to 'local'. */
    public function getRidePdfStorageDisk(): string;

    /**
     * Eloquent relationships to eager-load before rendering.
     *
     * @return list<string>
     */
    public function getRidePdfEagerLoads(): array;

    /**
     * Extra data merged into the Blade view alongside $document.
     *
     * @return array<string, mixed>
     */
    public function getRidePdfViewData(): array;

    /** Browser download filename, e.g. 'fac-001-001-000000001-ride.pdf'. */
    public function getRidePdfDownloadFilename(): string;

    /**
     * Human-readable SRI document type label for the RIDE header box.
     * Examples: 'FACTURA', 'NOTA DE CRÉDITO', 'NOTA DE DÉBITO'
     */
    public function getRideSriDocumentTypeLabel(): string;
}
