<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Modules\Core\Enums\FileTypeEnum;

/**
 * Contract for all PDF-able ERP documents.
 *
 * Any model that can generate a PDF (Quotation, SalesOrder, Invoice, PurchaseOrder, etc.)
 * must implement this interface. The generic infrastructure (job, service, action) uses
 * only these four methods — meaning no new infrastructure is needed per document type.
 */
interface GeneratesPdf
{
    /**
     * The fully-qualified Blade view name for this document type.
     *
     * Example: 'sales::pdf.quotation'
     */
    public function getPdfView(): string;

    /**
     * The FileTypeEnum case that governs disk, visibility, and path template.
     *
     * Example: FileTypeEnum::QuotationPdf
     */
    public function getPdfFileType(): FileTypeEnum;

    /**
     * Eloquent relationships to eager-load before rendering the PDF view.
     *
     * @return array<string>
     */
    public function getPdfEagerLoads(): array;

    /**
     * Extra data merged into the Blade view alongside $document.
     *
     * @return array<string, mixed>
     */
    public function getPdfViewData(): array;

    /**
     * The filename presented to the browser when downloading the PDF.
     *
     * The file stored on disk always uses the slug format. This method
     * controls only the Content-Disposition header filename.
     *
     * Example: 'fac-2024-001.pdf' or '4901201234567890123456789012345678901234567890.pdf'
     */
    public function getPdfDownloadFilename(): string;
}
