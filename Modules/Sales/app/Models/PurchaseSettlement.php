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
use Modules\Core\Support\Models\BaseModel;
use Modules\Inventory\Models\Warehouse;
use Modules\People\Enums\RoleEnum;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Contracts\HasSriSequential;
use Modules\Sales\Enums\DocumentTypeEnum;
use Modules\Sales\Enums\PurchaseSettlementStatusEnum;
use Modules\Sales\Support\Pdf\CommercialDocumentPdfDataBuilder;
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

final class PurchaseSettlement extends BaseModel implements Approvable, DocumentContract, GeneratesPdf, GeneratesRidePdf, HasElectronicBilling, HasSriSequential
{
    use HasApprovals;
    use HasDocumentBehavior;
    use HasElectronicDocumentState {
        isElectronicDocumentMutable as traitIsElectronicDocumentMutable;
    }
    use HasElectronicEvents;
    use HasFactory;
    use HasSriTechnicalExchanges;
    use HasTenancy;
    use HasYearlyAutoCode;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sales_purchase_settlements';

    protected $fillable = [
        'company_id',
        'code',
        'supplier_id',
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
        'currency_code',
        'status',
        'issue_date',
        'voided_at',
        'voided_reason',
        'notes',
        'subtotal',
        'tax_base',
        'tax_amount',
        'total',
        'sri_payments',
        'additional_info',
        'metadata',
        'supplier_email',
        'warehouse_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'electronic_status' => ElectronicStatusEnum::class,
            'electronic_submitted_at' => 'datetime',
            'electronic_authorized_at' => 'datetime',
            'correction_status' => ElectronicCorrectionStatusEnum::class,
            'correction_requested_at' => 'datetime',
            'corrected_at' => 'datetime',
            'status' => PurchaseSettlementStatusEnum::class,
            'issue_date' => 'date',
            'voided_at' => 'datetime',
            'subtotal' => 'decimal:4',
            'tax_base' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total' => 'decimal:4',
            'sri_payments' => 'array',
            'additional_info' => 'array',
            'metadata' => 'array',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'LC';
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function isSnapshotStale(): bool
    {
        if (blank($this->supplier_id)) {
            return false;
        }

        $partner = $this->supplier;

        if (! $partner) {
            return false;
        }

        return $this->supplier_name !== $partner->legal_name
            || $this->supplier_identification !== $partner->identification_number
            || $this->supplier_address !== $partner->tax_address;
    }

    public function refreshSnapshot(): void
    {
        $partner = $this->supplier;

        if (! $partner) {
            return;
        }

        $this->supplier_name = $partner->legal_name;
        $this->supplier_identification_type = $partner->identification_type;
        $this->supplier_identification = $partner->identification_number;
        $this->supplier_address = $partner->tax_address;
        $this->supplier_email = $partner->email;
        $this->clearPdfMetadata();
        $this->saveQuietly();
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseSettlementItem::class, 'purchase_settlement_id')->orderBy('sort_order');
    }

    public function withholdings(): HasMany
    {
        return $this->hasMany(Withholding::class, 'source_purchase_settlement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    /**
     * @return array<string, ApprovalFlow>
     */
    public function getApprovalFlows(): array
    {
        return [
            'settlement_approval' => ApprovalFlow::make('settlement_approval')
                ->step(
                    ApprovalStep::make('finance_director')
                        ->role(RoleEnum::FINANCE_DIRECTOR->value)
                        ->atLeast(1)
                ),
        ];
    }

    public function isElectronicDocumentMutable(): bool
    {
        if ($this->withholdings()->exists()) {
            return false;
        }

        return $this->traitIsElectronicDocumentMutable();
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
        return DocumentTypeEnum::PurchaseSettlement;
    }

    // ─── HasElectronicBilling ───────────────────────────────────────────────

    public function getSriDocumentTypeCode(): string
    {
        return '03';
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
        return ['items', 'company'];
    }

    public function getXmlStoragePath(string $tenantRuc): string
    {
        $year = $this->issue_date?->year ?? now()->year;

        return "tenants/{$tenantRuc}/documents/xml/purchase-settlements/{$year}/{$this->access_key}.xml";
    }

    public function getRideStoragePath(string $tenantRuc): string
    {
        $year = $this->issue_date?->year ?? now()->year;

        return "tenants/{$tenantRuc}/documents/ride/purchase-settlements/{$year}/{$this->access_key}.xml";
    }

    // ─── GeneratesPdf ───────────────────────────────────────────────────────

    public function getPdfView(): string
    {
        return 'sales::pdf.v1.sri.purchase-settlement';
    }

    public function getPdfFileType(): FileTypeEnum
    {
        return FileTypeEnum::PurchaseSettlementPdf;
    }

    public function getPdfEagerLoads(): array
    {
        return ['items', 'company'];
    }

    public function getPdfViewData(): array
    {
        return CommercialDocumentPdfDataBuilder::build($this);
    }

    public function getPdfDownloadFilename(): string
    {
        return str($this->code)->slug()->append('.pdf')->toString();
    }

    // ─── GeneratesRidePdf ───────────────────────────────────────────────────

    public function getRidePdfView(): string
    {
        return 'sales::pdf.v2.ride-purchase-settlement';
    }

    public function getRidePdfType(): string
    {
        return 'purchase-settlement';
    }

    public function getRidePdfStoragePath(string $tenantRuc): string
    {
        $year = $this->issue_date?->year ?? now()->year;

        return "tenants/{$tenantRuc}/pdfs/v2/ride/purchase-settlements/{$year}/{$this->access_key}.pdf";
    }

    public function getRidePdfStorageDisk(): string
    {
        return 'local';
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
        return 'LIQUIDACIÓN DE COMPRA';
    }

    protected function supportsInPlaceRejectedElectronicCorrection(): bool
    {
        return true;
    }
}
