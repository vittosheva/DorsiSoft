<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Traits\EnumTrait;

enum FileTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    // Core file types
    case ProductImages = 'product_images';
    case InventoryQrCodes = 'inventory_qr_codes';
    case CertificateFiles = 'certificate_files';
    case UserAvatars = 'user_avatars';

    // Document types
    case DocumentFiles = 'document_files';
    case InvoiceAttachments = 'invoice_attachments';
    case PurchaseOrderDocuments = 'purchase_order_documents';
    case ReceiptImages = 'receipt_images';
    case SriDocuments = 'sri_documents';
    case SriSignedXml = 'sri_signed_xml';
    case SriRideXml = 'sri_ride_xml';
    case SriRidePdf = 'sri_ride_pdf';

    // Media types
    case WarehousePhotos = 'warehouse_photos';
    case BannerImages = 'banner_images';
    case BrandLogos = 'brand_logos';
    case CompanyLogos = 'company_logos';
    case VideoContent = 'video_content';

    // HR types
    case EmployeeDocuments = 'employee_documents';

    // System types
    case BackupFiles = 'backup_files';
    case ImportFiles = 'import_files';
    case ExportFiles = 'export_files';

    // Catalog types
    case CatalogDocuments = 'catalog_documents';

    // Partner OCR types
    case PartnerOcrDocuments = 'partner_ocr_documents';
    case DocumentExtractionUploads = 'document_extraction_uploads';

    // Generated PDF types
    case QuotationPdf = 'quotation_pdf';
    case SalesOrderPdf = 'sales_order_pdf';
    case InvoicePdf = 'invoice_pdf';
    case CollectionAllocationReversalPdf = 'collection_allocation_reversal_pdf';
    case CreditNotePdf = 'credit_note_pdf';
    case DebitNotePdf = 'debit_note_pdf';
    case PurchaseSettlementPdf = 'purchase_settlement_pdf';
    case WithholdingPdf = 'withholding_pdf';
    case DeliveryGuidePdf = 'delivery_guide_pdf';

    public const DEFAULT = self::ProductImages->value;

    public function getLabel(): ?string
    {
        return $this->translate();
    }

    public function translate(): string
    {
        return match ($this) {
            self::ProductImages => __('Product Images'),
            self::InventoryQrCodes => __('Inventory QR Codes'),
            self::CertificateFiles => __('Certificate Files'),
            self::UserAvatars => __('User Avatars'),
            self::DocumentFiles => __('Document Files'),
            self::InvoiceAttachments => __('Invoice Attachments'),
            self::PurchaseOrderDocuments => __('Purchase Order Documents'),
            self::ReceiptImages => __('Receipt Images'),
            self::SriDocuments => __('SRI Documents'),
            self::SriSignedXml => __('SRI Signed XML'),
            self::SriRideXml => __('SRI Authorized RIDE XML'),
            self::SriRidePdf => __('SRI RIDE PDF'),
            self::WarehousePhotos => __('Warehouse Photos'),
            self::BannerImages => __('Banner Images'),
            self::BrandLogos => __('Brand Logos'),
            self::CompanyLogos => __('Company Logos'),
            self::VideoContent => __('Video Content'),
            self::EmployeeDocuments => __('Employee Documents'),
            self::BackupFiles => __('Backup Files'),
            self::ImportFiles => __('Import Files'),
            self::ExportFiles => __('Export Files'),
            self::CatalogDocuments => __('Catalog Documents'),
            self::PartnerOcrDocuments => __('Partner OCR Documents'),
            self::DocumentExtractionUploads => __('Document Extraction Uploads'),
            self::QuotationPdf => __('Quotation'),
            self::SalesOrderPdf => __('Sales order'),
            self::InvoicePdf => __('Invoice'),
            self::CollectionAllocationReversalPdf => __('Collection Allocation Reversal'),
            self::CreditNotePdf => __('Credit Note'),
            self::DebitNotePdf => __('Debit Note'),
            self::PurchaseSettlementPdf => __('Purchase Settlement'),
            self::WithholdingPdf => __('Withholding'),
            self::DeliveryGuidePdf => __('Delivery Guide'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ProductImages => Color::Sky,
            self::InventoryQrCodes => Color::Sky,
            self::CertificateFiles => Color::Amber,
            self::UserAvatars => Color::Rose,
            self::DocumentFiles => Color::Slate,
            self::InvoiceAttachments => Color::Orange,
            self::PurchaseOrderDocuments => Color::Green,
            self::ReceiptImages => Color::Yellow,
            self::SriDocuments => Color::Red,
            self::SriSignedXml => Color::Red,
            self::SriRideXml => Color::Red,
            self::SriRidePdf => Color::Red,
            self::WarehousePhotos => Color::Cyan,
            self::BannerImages => Color::Violet,
            self::BrandLogos => Color::Indigo,
            self::CompanyLogos => Color::Blue,
            self::VideoContent => Color::Fuchsia,
            self::EmployeeDocuments => Color::Teal,
            self::BackupFiles => Color::Purple,
            self::ImportFiles => Color::Lime,
            self::ExportFiles => Color::Emerald,
            self::CatalogDocuments => Color::Zinc,
            self::PartnerOcrDocuments => Color::Teal,
            self::DocumentExtractionUploads => Color::Sky,
            self::QuotationPdf => Color::Blue,
            self::SalesOrderPdf => Color::Green,
            self::InvoicePdf => Color::Orange,
            self::CollectionAllocationReversalPdf => Color::Red,
            self::CreditNotePdf => Color::Purple,
            self::DebitNotePdf => Color::Amber,
            self::PurchaseSettlementPdf => Color::Cyan,
            self::WithholdingPdf => Color::Rose,
            self::DeliveryGuidePdf => Color::Teal,
        };
    }

    public function getColorName(): string
    {
        return match ($this) {
            self::ProductImages => 'info',
            self::InventoryQrCodes => 'info',
            self::CertificateFiles => 'warning',
            self::UserAvatars => 'danger',
            self::DocumentFiles => 'secondary',
            self::InvoiceAttachments => 'warning',
            self::PurchaseOrderDocuments => 'success',
            self::ReceiptImages => 'warning',
            self::SriDocuments => 'danger',
            self::SriSignedXml => 'danger',
            self::SriRideXml => 'danger',
            self::SriRidePdf => 'danger',
            self::WarehousePhotos => 'info',
            self::BannerImages => 'primary',
            self::BrandLogos => 'info',
            self::CompanyLogos => 'primary',
            self::VideoContent => 'primary',
            self::EmployeeDocuments => 'info',
            self::BackupFiles => 'secondary',
            self::ImportFiles => 'warning',
            self::ExportFiles => 'success',
            self::CatalogDocuments => 'secondary',
            self::PartnerOcrDocuments => 'info',
            self::DocumentExtractionUploads => 'info',
            self::QuotationPdf => 'primary',
            self::SalesOrderPdf => 'success',
            self::InvoicePdf => 'warning',
            self::CollectionAllocationReversalPdf => 'danger',
            self::CreditNotePdf => 'primary',
            self::DebitNotePdf => 'warning',
            self::PurchaseSettlementPdf => 'info',
            self::WithholdingPdf => 'danger',
            self::DeliveryGuidePdf => 'info',
        };
    }

    public function getIcon(): BackedEnum|string|null
    {
        return match ($this) {
            self::ProductImages => Heroicon::Photo,
            self::InventoryQrCodes => Heroicon::QrCode,
            self::CertificateFiles => Heroicon::DocumentText,
            self::UserAvatars => Heroicon::User,
            self::DocumentFiles => Heroicon::DocumentDuplicate,
            self::InvoiceAttachments => Heroicon::DocumentCheck,
            self::PurchaseOrderDocuments => Heroicon::ShoppingCart,
            self::ReceiptImages => Heroicon::ReceiptPercent,
            self::SriDocuments => Heroicon::DocumentDuplicate,
            self::SriSignedXml => Heroicon::DocumentDuplicate,
            self::SriRideXml => Heroicon::DocumentCheck,
            self::SriRidePdf => Heroicon::DocumentArrowDown,
            self::WarehousePhotos => Heroicon::BuildingOffice,
            self::BannerImages => Heroicon::Photo,
            self::BrandLogos => Heroicon::Squares2x2,
            self::CompanyLogos => Heroicon::BuildingOffice2,
            self::VideoContent => Heroicon::VideoCamera,
            self::EmployeeDocuments => Heroicon::Users,
            self::BackupFiles => Heroicon::CircleStack,
            self::ImportFiles => Heroicon::ArrowDownTray,
            self::ExportFiles => Heroicon::ArrowUpTray,
            self::CatalogDocuments => Heroicon::BookOpen,
            self::PartnerOcrDocuments => Heroicon::DocumentMagnifyingGlass,
            self::DocumentExtractionUploads => Heroicon::ArrowUpTray,
            self::QuotationPdf => Heroicon::DocumentArrowDown,
            self::SalesOrderPdf => Heroicon::DocumentArrowDown,
            self::InvoicePdf => Heroicon::DocumentArrowDown,
            self::CollectionAllocationReversalPdf => Heroicon::DocumentArrowDown,
            self::CreditNotePdf => Heroicon::DocumentArrowDown,
            self::DebitNotePdf => Heroicon::DocumentArrowDown,
            self::PurchaseSettlementPdf => Heroicon::DocumentArrowDown,
            self::WithholdingPdf => Heroicon::DocumentArrowDown,
            self::DeliveryGuidePdf => Heroicon::DocumentArrowDown,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ProductImages => __('Product catalog images'),
            self::InventoryQrCodes => __('Inventory QR Codes'),
            self::CertificateFiles => __('Digital certificate files (.p12, .pfx)'),
            self::UserAvatars => __('User profile avatars'),
            self::DocumentFiles => __('General documents and reports'),
            self::InvoiceAttachments => __('Attachments for fiscal documents'),
            self::PurchaseOrderDocuments => __('Purchase orders, quotes, and related documents'),
            self::ReceiptImages => __('Scanned receipts and physical invoices'),
            self::SriDocuments => __('SRI fiscal documents and proofs'),
            self::SriSignedXml => __('Signed XML sent to SRI'),
            self::SriRideXml => __('Authorized XML returned by SRI'),
            self::SriRidePdf => __('RIDE PDF v2 representation'),
            self::WarehousePhotos => __('Inventory and warehouse location photos'),
            self::BannerImages => __('Promotional banners and carousel images'),
            self::BrandLogos => __('Brand logos for catalog'),
            self::CompanyLogos => __('Company logos for invoices and reports'),
            self::VideoContent => __('Product videos and tutorials'),
            self::EmployeeDocuments => __('HR documents, contracts, and certificates'),
            self::BackupFiles => __('System backup files'),
            self::ImportFiles => __('Files for bulk data import'),
            self::ExportFiles => __('Downloads of reports and exported data'),
            self::CatalogDocuments => __('Technical specs and catalog PDFs'),
            self::PartnerOcrDocuments => __('PDFs uploaded for OCR text extraction'),
            self::DocumentExtractionUploads => __('Commercial documents uploaded for OCR extraction and suggestion parsing'),
            self::QuotationPdf => __('Generated PDF for quotation documents'),
            self::SalesOrderPdf => __('Generated PDF for sales order documents'),
            self::InvoicePdf => __('Generated PDF for invoice documents'),
            self::CollectionAllocationReversalPdf => __('Generated PDF for collection allocation reversal documents'),
            self::CreditNotePdf => __('Generated PDF for credit note documents'),
            self::DebitNotePdf => __('Generated PDF for debit note documents'),
            self::PurchaseSettlementPdf => __('Generated PDF for purchase settlement documents'),
            self::WithholdingPdf => __('Generated PDF for withholding documents'),
            self::DeliveryGuidePdf => __('Generated PDF for delivery guide documents'),
        };
    }

    public function getDisk(): string
    {
        return match ($this) {
            self::ProductImages => 'public',
            self::InventoryQrCodes => 'public',
            self::CertificateFiles => 'local',
            self::UserAvatars => 'public',
            self::DocumentFiles => 'local',
            self::InvoiceAttachments => 'local',
            self::PurchaseOrderDocuments => 'local',
            self::ReceiptImages => 'local',
            self::SriDocuments => 'local',
            self::SriSignedXml => 'local',
            self::SriRideXml => 'local',
            self::SriRidePdf => 'local',
            self::WarehousePhotos => 'public',
            self::BannerImages => 'public',
            self::BrandLogos => 'public',
            self::CompanyLogos => 'public',
            self::VideoContent => 'public',
            self::EmployeeDocuments => 'local',
            self::BackupFiles => 'local',
            self::ImportFiles => 'local',
            self::ExportFiles => 'public',
            self::CatalogDocuments => 'public',
            self::PartnerOcrDocuments => 'local',
            self::DocumentExtractionUploads => 'local',
            self::QuotationPdf => 'local',
            self::SalesOrderPdf => 'local',
            self::InvoicePdf => 'local',
            self::CollectionAllocationReversalPdf => 'local',
            self::CreditNotePdf => 'local',
            self::DebitNotePdf => 'local',
            self::PurchaseSettlementPdf => 'local',
            self::WithholdingPdf => 'local',
            self::DeliveryGuidePdf => 'local',
        };
    }

    public function getVisibility(): string
    {
        return match ($this) {
            self::ProductImages => 'public',
            self::InventoryQrCodes => 'public',
            self::CertificateFiles => 'private',
            self::UserAvatars => 'public',
            self::DocumentFiles => 'private',
            self::InvoiceAttachments => 'private',
            self::PurchaseOrderDocuments => 'private',
            self::ReceiptImages => 'private',
            self::SriDocuments => 'private',
            self::SriSignedXml => 'private',
            self::SriRideXml => 'private',
            self::SriRidePdf => 'private',
            self::WarehousePhotos => 'public',
            self::BannerImages => 'public',
            self::BrandLogos => 'public',
            self::CompanyLogos => 'public',
            self::VideoContent => 'public',
            self::EmployeeDocuments => 'private',
            self::BackupFiles => 'private',
            self::ImportFiles => 'private',
            self::ExportFiles => 'public',
            self::CatalogDocuments => 'public',
            self::PartnerOcrDocuments => 'private',
            self::DocumentExtractionUploads => 'private',
            self::QuotationPdf => 'private',
            self::SalesOrderPdf => 'private',
            self::InvoicePdf => 'private',
            self::CollectionAllocationReversalPdf => 'private',
            self::CreditNotePdf => 'private',
            self::DebitNotePdf => 'private',
            self::PurchaseSettlementPdf => 'private',
            self::WithholdingPdf => 'private',
            self::DeliveryGuidePdf => 'private',
        };
    }

    public function getAcceptedTypes(): array
    {
        return match ($this) {
            self::ProductImages => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            self::InventoryQrCodes => ['image/png', 'image/svg+xml'],
            self::CertificateFiles => ['application/x-pkcs12', '.p12', '.pfx'],
            self::UserAvatars => ['image/jpeg', 'image/png', 'image/webp'],
            self::DocumentFiles => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            self::InvoiceAttachments => ['application/pdf', 'image/jpeg', 'image/png'],
            self::PurchaseOrderDocuments => ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            self::ReceiptImages => ['image/jpeg', 'image/png', 'image/webp'],
            self::SriDocuments => ['application/pdf', 'text/xml', 'application/xml'],
            self::SriSignedXml => ['text/xml', 'application/xml'],
            self::SriRideXml => ['text/xml', 'application/xml'],
            self::SriRidePdf => ['application/pdf'],
            self::WarehousePhotos => ['image/jpeg', 'image/png', 'image/webp'],
            self::BannerImages => ['image/jpeg', 'image/png', 'image/webp'],
            self::BrandLogos => ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'],
            self::CompanyLogos => ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'],
            self::VideoContent => ['video/mp4', 'video/webm', 'video/quicktime'],
            self::EmployeeDocuments => ['application/pdf', 'image/jpeg', 'image/png'],
            self::BackupFiles => ['application/gzip', 'application/zip', 'application/x-tar'],
            self::ImportFiles => ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            self::ExportFiles => ['text/csv', 'application/pdf', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            self::CatalogDocuments => ['application/pdf', 'image/jpeg', 'image/png'],
            self::PartnerOcrDocuments => ['application/pdf'],
            self::DocumentExtractionUploads => ['application/pdf', 'image/jpeg', 'image/png'],
            self::QuotationPdf => ['application/pdf'],
            self::SalesOrderPdf => ['application/pdf'],
            self::InvoicePdf => ['application/pdf'],
            self::CollectionAllocationReversalPdf => ['application/pdf'],
            self::CreditNotePdf => ['application/pdf'],
            self::DebitNotePdf => ['application/pdf'],
            self::PurchaseSettlementPdf => ['application/pdf'],
            self::WithholdingPdf => ['application/pdf'],
            self::DeliveryGuidePdf => ['application/pdf'],
        };
    }

    public function getMaxSizeKb(): int
    {
        return match ($this) {
            self::ProductImages => 5120, // 5MB
            self::InventoryQrCodes => 5120, // 5MB
            self::CertificateFiles => 1024, // 1MB
            self::UserAvatars => 1024, // 1MB
            self::DocumentFiles => 20480, // 20MB
            self::InvoiceAttachments => 10240, // 10MB
            self::PurchaseOrderDocuments => 15360, // 15MB
            self::ReceiptImages => 5120, // 5MB
            self::SriDocuments => 10240, // 10MB
            self::SriSignedXml => 10240, // 10MB
            self::SriRideXml => 10240, // 10MB
            self::SriRidePdf => 5120, // 5MB
            self::WarehousePhotos => 8192, // 8MB
            self::BannerImages => 5120, // 5MB
            self::BrandLogos => 1024, // 1MB
            self::CompanyLogos => 2048, // 2MB
            self::VideoContent => 153600, // 150MB
            self::EmployeeDocuments => 5120, // 5MB
            self::BackupFiles => 524288, // 512MB
            self::ImportFiles => 30720, // 30MB
            self::ExportFiles => 51200, // 50MB
            self::CatalogDocuments => 20480, // 20MB
            self::PartnerOcrDocuments => 20480, // 20MB
            self::DocumentExtractionUploads => 20480, // 20MB
            self::QuotationPdf => 5120, // 5MB
            self::SalesOrderPdf => 5120, // 5MB
            self::InvoicePdf => 5120, // 5MB
            self::CollectionAllocationReversalPdf => 5120, // 5MB
            self::CreditNotePdf => 5120, // 5MB
            self::DebitNotePdf => 5120, // 5MB
            self::PurchaseSettlementPdf => 5120, // 5MB
            self::WithholdingPdf => 5120, // 5MB
            self::DeliveryGuidePdf => 5120, // 5MB
        };
    }

    public function getBasePath(): string
    {
        return match ($this) {
            self::ProductImages => '{tenant}/{resource_type}/{record_id}',
            self::InventoryQrCodes => '{tenant}/inventory/qr-codes/{filename}',
            self::CertificateFiles => '{tenant}/certificates/{record_id}',
            self::UserAvatars => '{tenant}/avatars/{record_id}',
            self::DocumentFiles => '{tenant}/documents/{record_id}',
            self::InvoiceAttachments => '{tenant}/invoices/{record_id}',
            self::PurchaseOrderDocuments => '{tenant}/purchases/{record_id}',
            self::ReceiptImages => '{tenant}/receipts/{record_id}',
            self::SriDocuments => '{tenant}/sri/{record_id}',
            self::SriSignedXml => '{tenant}/documents/xml/{document_type}/{year}/{filename}',
            self::SriRideXml => '{tenant}/documents/ride/{document_type}/{year}/{filename}',
            self::SriRidePdf => '{tenant}/pdfs/v2/ride/{document_type}/{year}/{filename}',
            self::WarehousePhotos => '{tenant}/warehouse/{record_id}',
            self::BannerImages => '{tenant}/banners/{record_id}',
            self::BrandLogos => '{tenant}/brands/{record_id}',
            self::CompanyLogos => '{tenant}/logos/{record_id}',
            self::VideoContent => '{tenant}/videos/{record_id}',
            self::EmployeeDocuments => '{tenant}/employees/{record_id}',
            self::BackupFiles => '{tenant}/backups/{record_id}',
            self::ImportFiles => '{tenant}/imports/{record_id}',
            self::ExportFiles => '{tenant}/exports/{record_id}',
            self::CatalogDocuments => '{tenant}/catalogs/{record_id}',
            self::PartnerOcrDocuments => '{tenant}/partners/ocr/{record_id}',
            self::DocumentExtractionUploads => '{tenant}/document-extractions/{record_id}',
            self::QuotationPdf => '{tenant}/pdfs/quotations/{record_id}',
            self::SalesOrderPdf => '{tenant}/pdfs/orders/{record_id}',
            self::InvoicePdf => '{tenant}/pdfs/invoices/{record_id}',
            self::CollectionAllocationReversalPdf => '{tenant}/pdfs/collection-allocation-reversals/{record_id}',
            self::CreditNotePdf => '{tenant}/pdfs/credit-notes/{record_id}',
            self::DebitNotePdf => '{tenant}/pdfs/debit-notes/{record_id}',
            self::PurchaseSettlementPdf => '{tenant}/pdfs/purchase-settlements/{record_id}',
            self::WithholdingPdf => '{tenant}/pdfs/withholdings/{record_id}',
            self::DeliveryGuidePdf => '{tenant}/pdfs/delivery-guides/{record_id}',
        };
    }

    public function isPublic(): bool
    {
        return $this->getVisibility() === 'public';
    }

    public function isPrivate(): bool
    {
        return $this->getVisibility() === 'private';
    }

    public function isImage(): bool
    {
        return in_array($this, [
            self::ProductImages,
            self::UserAvatars,
            self::WarehousePhotos,
            self::BannerImages,
            self::BrandLogos,
            self::CompanyLogos,
            self::ReceiptImages,
        ], true);
    }

    public function isDocument(): bool
    {
        return in_array($this, [
            self::DocumentFiles,
            self::InvoiceAttachments,
            self::PurchaseOrderDocuments,
            self::SriDocuments,
            self::SriSignedXml,
            self::SriRideXml,
            self::SriRidePdf,
            self::EmployeeDocuments,
            self::CatalogDocuments,
            self::PartnerOcrDocuments,
            self::DocumentExtractionUploads,
            self::QuotationPdf,
            self::SalesOrderPdf,
            self::InvoicePdf,
            self::CollectionAllocationReversalPdf,
            self::CreditNotePdf,
            self::DebitNotePdf,
            self::PurchaseSettlementPdf,
            self::WithholdingPdf,
            self::DeliveryGuidePdf,
        ], true);
    }

    public function isMedia(): bool
    {
        return in_array($this, [
            self::VideoContent,
            self::BannerImages,
        ], true);
    }

    public function isSystemFile(): bool
    {
        return in_array($this, [
            self::BackupFiles,
            self::ImportFiles,
            self::ExportFiles,
        ], true);
    }
}
