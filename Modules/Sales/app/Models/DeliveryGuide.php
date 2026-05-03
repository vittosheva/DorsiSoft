<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Contracts\DocumentContract;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Models\Traits\HasDocumentBehavior;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Models\Traits\HasYearlyAutoCode;
use Modules\Core\Services\FileStoragePathService;
use Modules\Core\Support\Models\BaseModel;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Contracts\HasSriSequential;
use Modules\Sales\Enums\DeliveryGuideCarrierTypeEnum;
use Modules\Sales\Enums\DeliveryGuideStatusEnum;
use Modules\Sales\Enums\DocumentTypeEnum;
use Modules\Sales\Models\Traits\AutoAssignsDocumentType;
use Modules\Sri\Concerns\HasElectronicDocumentState;
use Modules\Sri\Concerns\HasElectronicEvents;
use Modules\Sri\Concerns\HasSriTechnicalExchanges;
use Modules\Sri\Contracts\GeneratesRidePdf;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicCorrectionStatusEnum;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\System\Models\DocumentType;

final class DeliveryGuide extends BaseModel implements DocumentContract, GeneratesPdf, GeneratesRidePdf, HasElectronicBilling, HasSriSequential
{
    use AutoAssignsDocumentType;
    use HasDocumentBehavior;
    use HasElectronicDocumentState;
    use HasElectronicEvents;
    use HasFactory;
    use HasSriTechnicalExchanges;
    use HasTenancy;
    use HasYearlyAutoCode;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sales_delivery_guides';

    protected $fillable = [
        'company_id',
        'document_type_id',
        'code',
        'carrier_id',
        'carrier_name',
        'carrier_identification',
        'carrier_plate',
        'carrier_driver_name',
        'carrier_type',
        'establishment_code',
        'emission_point_code',
        'sequential_number',
        'access_key',
        'electronic_status',
        'electronic_submitted_at',
        'electronic_authorized_at',
        'correction_status',
        'correction_source_id',
        'superseded_by_id',
        'correction_requested_at',
        'corrected_at',
        'correction_reason',
        'status',
        'issue_date',
        'transport_start_date',
        'transport_end_date',
        'origin_address',
        'voided_at',
        'voided_reason',
        'notes',
        'additional_info',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryGuideStatusEnum::class,
            'carrier_type' => DeliveryGuideCarrierTypeEnum::class,
            'electronic_status' => ElectronicStatusEnum::class,
            'electronic_submitted_at' => 'datetime',
            'electronic_authorized_at' => 'datetime',
            'correction_status' => ElectronicCorrectionStatusEnum::class,
            'correction_requested_at' => 'datetime',
            'corrected_at' => 'datetime',
            'issue_date' => 'date',
            'transport_start_date' => 'date',
            'transport_end_date' => 'date',
            'voided_at' => 'datetime',
            'additional_info' => 'array',
            'metadata' => 'array',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'GRE';
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'carrier_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DeliveryGuideRecipient::class, 'delivery_guide_id')->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function isSnapshotStale(): bool
    {
        if (blank($this->carrier_id)) {
            return false;
        }

        $carrier = $this->carrier;

        if (! $carrier) {
            return false;
        }

        return $this->carrier_name !== $carrier->legal_name
            || $this->carrier_identification !== $carrier->identification_number;
    }

    public function refreshSnapshot(): void
    {
        $carrier = $this->carrier;

        if (! $carrier) {
            return;
        }

        $this->carrier_name = $carrier->legal_name;
        $this->carrier_identification = $carrier->identification_number;
        $this->clearPdfMetadata();
        $this->save();
    }

    // ─── HasSriSequential ───────────────────────────────────────────────────

    public function getSriSequentialCode(): ?string
    {
        if (blank($this->establishment_code) || blank($this->emission_point_code) || blank($this->sequential_number)) {
            return null;
        }

        return "{$this->establishment_code}-{$this->emission_point_code}-{$this->sequential_number}";
    }

    public function getSriDocumentType(): DocumentTypeEnum
    {
        return DocumentTypeEnum::DeliveryGuide;
    }

    // ─── GeneratesPdf ───────────────────────────────────────────────────────

    public function getPdfView(): string
    {
        return 'sales::pdf.v1.sri.delivery-guide';
    }

    public function getPdfFileType(): FileTypeEnum
    {
        return FileTypeEnum::DeliveryGuidePdf;
    }

    public function getPdfEagerLoads(): array
    {
        return ['recipients.items', 'company:id,default_currency_id,logo_pdf_url,legal_name,ruc,phone,tax_address'];
    }

    public function getPdfViewData(): array
    {
        return [];
    }

    public function getPdfDownloadFilename(): string
    {
        return str($this->code)->slug()->append('.pdf')->toString();
    }

    // ─── GeneratesRidePdf ───────────────────────────────────────────────────

    public function getRidePdfView(): string
    {
        return 'sales::pdf.v2.ride-delivery-guide';
    }

    public function getRidePdfType(): string
    {
        return 'delivery-guide';
    }

    public function getRidePdfStoragePath(string $tenantRuc): string
    {
        return FileStoragePathService::getPath(
            FileTypeEnum::SriRidePdf,
            tenantId: $tenantRuc,
            context: [
                'document_type' => 'delivery-guides',
                'year' => (string) ($this->transport_start_date?->year ?? now()->year),
                'filename' => "{$this->access_key}.pdf",
            ],
        );
    }

    public function getRidePdfStorageDisk(): string
    {
        return FileStoragePathService::getDisk(FileTypeEnum::SriRidePdf);
    }

    public function getRidePdfEagerLoads(): array
    {
        return ['recipients.items', 'company', 'creator:id,name'];
    }

    public function getRidePdfViewData(): array
    {
        return [];
    }

    public function getRidePdfDownloadFilename(): string
    {
        return str($this->code)->slug()->append('-ride.pdf')->toString();
    }

    public function getRideSriDocumentTypeLabel(): string
    {
        return 'GUÍA DE REMISIÓN';
    }

    // ─── HasElectronicBilling ───────────────────────────────────────────────

    public function getSriDocumentTypeCode(): string
    {
        return '06';
    }

    public function getElectronicStatus(): ?ElectronicStatusEnum
    {
        return $this->electronic_status;
    }

    public function getAccessKey(): ?string
    {
        return $this->access_key;
    }

    public function getElectronicEagerLoads(): array
    {
        return ['recipients.items', 'carrier.carrierVehicles', 'company'];
    }

    public function getXmlStoragePath(string $tenantRuc): string
    {
        return FileStoragePathService::getPath(
            FileTypeEnum::SriSignedXml,
            tenantId: $tenantRuc,
            context: [
                'document_type' => 'delivery-guides',
                'year' => (string) ($this->transport_start_date?->year ?? now()->year),
                'filename' => "{$this->access_key}.xml",
            ],
        );
    }

    public function getRideStoragePath(string $tenantRuc): string
    {
        return FileStoragePathService::getPath(
            FileTypeEnum::SriRideXml,
            tenantId: $tenantRuc,
            context: [
                'document_type' => 'delivery-guides',
                'year' => (string) ($this->transport_start_date?->year ?? now()->year),
                'filename' => "{$this->access_key}.xml",
            ],
        );
    }
}
