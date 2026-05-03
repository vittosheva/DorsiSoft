<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
use Modules\People\Enums\RoleEnum;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Contracts\HasSriSequential;
use Modules\Sales\Enums\DocumentTypeEnum;
use Modules\Sales\Enums\WithholdingStatusEnum;
use Modules\Sales\Models\Traits\AutoAssignsDocumentType;
use Modules\Sri\Concerns\HasElectronicDocumentState;
use Modules\Sri\Concerns\HasElectronicEvents;
use Modules\Sri\Concerns\HasSriTechnicalExchanges;
use Modules\Sri\Contracts\GeneratesRidePdf;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicCorrectionStatusEnum;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\System\Models\DocumentType;
use Modules\Workflow\Approval\ApprovalFlow;
use Modules\Workflow\Approval\ApprovalStep;
use Modules\Workflow\Contracts\Approvable;
use Modules\Workflow\Traits\HasApprovals;

final class Withholding extends BaseModel implements Approvable, DocumentContract, GeneratesPdf, GeneratesRidePdf, HasElectronicBilling, HasSriSequential
{
    use AutoAssignsDocumentType;
    use HasApprovals;
    use HasDocumentBehavior;
    use HasElectronicDocumentState;
    use HasElectronicEvents;
    use HasFactory;
    use HasSriTechnicalExchanges;
    use HasTenancy;
    use HasYearlyAutoCode;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sales_withholdings';

    protected $fillable = [
        'company_id',
        'document_type_id',
        'code',
        'business_partner_id',
        'supplier_name',
        'supplier_identification_type',
        'supplier_identification',
        'supplier_address',
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
        'period_fiscal',
        'status',
        'issue_date',
        'voided_at',
        'voided_reason',
        'notes',
        'source_document_type',
        'source_document_number',
        'source_document_date',
        'source_purchase_settlement_id',
        'additional_info',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => WithholdingStatusEnum::class,
            'electronic_status' => ElectronicStatusEnum::class,
            'electronic_submitted_at' => 'datetime',
            'electronic_authorized_at' => 'datetime',
            'correction_status' => ElectronicCorrectionStatusEnum::class,
            'correction_requested_at' => 'datetime',
            'corrected_at' => 'datetime',
            'issue_date' => 'date',
            'source_document_date' => 'date',
            'voided_at' => 'datetime',
            'additional_info' => 'array',
            'metadata' => 'array',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'RET';
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function getSriSequentialCode(): ?string
    {
        if (blank($this->establishment_code) || blank($this->emission_point_code) || blank($this->sequential_number)) {
            return null;
        }

        return "{$this->establishment_code}-{$this->emission_point_code}-{$this->sequential_number}";
    }

    public function isSnapshotStale(): bool
    {
        if (blank($this->business_partner_id)) {
            return false;
        }

        $partner = $this->businessPartner;

        if (! $partner) {
            return false;
        }

        return $this->supplier_name !== $partner->legal_name
            || $this->supplier_identification !== $partner->identification_number
            || $this->supplier_address !== $partner->tax_address;
    }

    public function refreshSnapshot(): void
    {
        $partner = $this->businessPartner;

        if (! $partner) {
            return;
        }

        $this->supplier_name = $partner->legal_name;
        $this->supplier_identification_type = $partner->identification_type;
        $this->supplier_identification = $partner->identification_number;
        $this->supplier_address = $partner->tax_address;
        $this->clearPdfMetadata();
        $this->save();
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this
            ->belongsTo(BusinessPartner::class, 'business_partner_id')
            ->suppliers();
    }

    public function items(): HasMany
    {
        return $this->hasMany(WithholdingItem::class, 'withholding_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    public function sourcePurchaseSettlement(): BelongsTo
    {
        return $this->belongsTo(PurchaseSettlement::class, 'source_purchase_settlement_id');
    }

    /**
     * @return array<string, ApprovalFlow>
     */
    public function getApprovalFlows(): array
    {
        return [
            'withholding_release' => ApprovalFlow::make('withholding_release')
                ->step(
                    ApprovalStep::make('accountant')
                        ->role(RoleEnum::ACCOUNTANT->value)
                        ->atLeast(1)
                ),
        ];
    }

    // ─── HasSriSequential ───────────────────────────────────────────────────

    public function getSriDocumentType(): DocumentTypeEnum
    {
        return DocumentTypeEnum::Withholding;
    }

    // ─── GeneratesPdf ───────────────────────────────────────────────────────

    public function getPdfView(): string
    {
        return 'sales::pdf.v1.sri.withholding';
    }

    public function getPdfFileType(): FileTypeEnum
    {
        return FileTypeEnum::WithholdingPdf;
    }

    public function getPdfEagerLoads(): array
    {
        return ['items', 'company:id,default_currency_id,logo_pdf_url,legal_name,ruc,phone,tax_address'];
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
        return 'sales::pdf.v2.ride-withholding';
    }

    public function getRidePdfType(): string
    {
        return 'withholding';
    }

    public function getRidePdfStoragePath(string $tenantRuc): string
    {
        return FileStoragePathService::getPath(
            FileTypeEnum::SriRidePdf,
            tenantId: $tenantRuc,
            context: [
                'document_type' => 'withholdings',
                'year' => (string) ($this->issue_date?->year ?? now()->year),
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
        return ['items', 'company', 'creator:id,name'];
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
        return 'COMPROBANTE DE RETENCIÓN';
    }

    // ─── HasElectronicBilling ───────────────────────────────────────────────

    public function getSriDocumentTypeCode(): string
    {
        return '07';
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
        return ['items', 'company', 'businessPartner:id,legal_name,identification_number,identification_type'];
    }

    public function getXmlStoragePath(string $tenantRuc): string
    {
        return FileStoragePathService::getPath(
            FileTypeEnum::SriSignedXml,
            tenantId: $tenantRuc,
            context: [
                'document_type' => 'withholdings',
                'year' => (string) ($this->issue_date?->year ?? now()->year),
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
                'document_type' => 'withholdings',
                'year' => (string) ($this->issue_date?->year ?? now()->year),
                'filename' => "{$this->access_key}.xml",
            ],
        );
    }

    protected function totalWithheld(): Attribute
    {
        return Attribute::get(fn (): string => number_format($this->items->sum('withheld_amount'), 2, '.', ''));
    }
}
