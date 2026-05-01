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
use Modules\Core\Models\Traits\HasCustomerSnapshot;
use Modules\Core\Models\Traits\HasDocumentBehavior;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Models\Traits\HasYearlyAutoCode;
use Modules\Core\Support\Models\BaseModel;
use Modules\Finance\Models\Collection;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Models\CollectionAllocationReversal;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\People\Enums\RoleEnum;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Casts\MoneyAmount;
use Modules\Sales\Contracts\HasSriSequential;
use Modules\Sales\Enums\CreditNoteReasonEnum;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Enums\DocumentTypeEnum;
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
use Modules\Workflow\Enums\ApprovalFlowKey;
use Modules\Workflow\Traits\HasApprovals;

final class CreditNote extends BaseModel implements Approvable, DocumentContract, GeneratesPdf, GeneratesRidePdf, HasElectronicBilling, HasSriSequential
{
    use HasApprovals;
    use HasCustomerSnapshot;
    use HasDocumentBehavior;
    use HasElectronicDocumentState;
    use HasElectronicEvents;
    use HasFactory;
    use HasSriTechnicalExchanges;
    use HasTenancy;
    use HasYearlyAutoCode;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sales_credit_notes';

    protected $fillable = [
        'company_id',
        'code',
        'invoice_id',
        'collection_id',
        'collection_allocation_reversal_id',
        'business_partner_id',
        'customer_name',
        'customer_trade_name',
        'customer_identification_type',
        'customer_identification',
        'customer_address',
        'customer_email',
        'customer_phone',
        'currency_code',
        'subtotal',
        'tax_amount',
        'total',
        'applied_amount',
        'refunded_amount',
        'status',
        'reason',
        'reason_code',
        'access_key',
        'establishment_code',
        'emission_point_code',
        'sequential_number',
        'ext_invoice_code',
        'ext_invoice_date',
        'ext_invoice_auth_number',
        'notes',
        'sri_payments',
        'additional_info',
        'metadata',
        'issue_date',
        'voided_at',
        'voided_reason',
        'electronic_status',
        'electronic_submitted_at',
        'electronic_authorized_at',
        'correction_status',
        'correction_source_id',
        'superseded_by_id',
        'correction_requested_at',
        'corrected_at',
        'correction_reason',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CreditNoteStatusEnum::class,
            'electronic_status' => ElectronicStatusEnum::class,
            'electronic_submitted_at' => 'datetime',
            'electronic_authorized_at' => 'datetime',
            'correction_status' => ElectronicCorrectionStatusEnum::class,
            'correction_requested_at' => 'datetime',
            'corrected_at' => 'datetime',
            'reason_code' => CreditNoteReasonEnum::class,
            'subtotal' => MoneyAmount::class,
            'tax_amount' => MoneyAmount::class,
            'total' => MoneyAmount::class,
            'applied_amount' => MoneyAmount::class,
            'refunded_amount' => MoneyAmount::class,
            'ext_invoice_date' => 'date',
            'sri_payments' => 'json',
            'additional_info' => 'json',
            'issue_date' => 'date',
            'voided_at' => 'datetime',
            'customer_email' => 'json',
            'metadata' => 'json',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'NC';
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function getRemainingBalance(): string
    {
        $consumed = bcadd(
            CollectionAllocationMath::normalize($this->applied_amount),
            CollectionAllocationMath::normalize($this->refunded_amount),
            CollectionAllocationMath::SCALE
        );

        return CollectionAllocationMath::pending($this->total, $consumed);
    }

    public function isFullyConsumed(): bool
    {
        return CollectionAllocationMath::isEffectivelyZero($this->getRemainingBalance());
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function getExplicitlyAllocatedAmount(): string
    {
        $allocatedAmount = CollectionAllocation::query()
            ->where('credit_note_id', $this->getKey())
            ->whereHas('collection', fn ($query) => $query->whereNull('voided_at'))
            ->sum('amount');

        return CollectionAllocationMath::normalize($allocatedAmount);
    }

    public function getAvailableCollectionBalance(): string
    {
        $afterRefund = CollectionAllocationMath::pending(
            (string) $this->total,
            CollectionAllocationMath::normalize($this->refunded_amount)
        );

        return CollectionAllocationMath::pending(
            $afterRefund,
            $this->getExplicitlyAllocatedAmount()
        );
    }

    public function getSriSequentialCode(): ?string
    {
        if (blank($this->establishment_code) || blank($this->emission_point_code) || blank($this->sequential_number)) {
            return null;
        }

        return "{$this->establishment_code}-{$this->emission_point_code}-{$this->sequential_number}";
    }

    public function getSriDocumentType(): DocumentTypeEnum
    {
        return DocumentTypeEnum::CreditNote;
    }

    // HasElectronicBilling implementation

    public function getSriDocumentTypeCode(): string
    {
        return '04';
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
        return ['items.taxes', 'company', 'invoice:id,code,establishment_code,emission_point_code,sequential_number,issue_date'];
    }

    public function getXmlStoragePath(string $tenantRuc): string
    {
        $year = $this->issue_date?->year ?? now()->year;

        return "tenants/{$tenantRuc}/documents/xml/credit-notes/{$year}/{$this->access_key}.xml";
    }

    public function getRideStoragePath(string $tenantRuc): string
    {
        $year = $this->issue_date?->year ?? now()->year;

        return "tenants/{$tenantRuc}/documents/ride/credit-notes/{$year}/{$this->access_key}.xml";
    }

    /**
     * Factura original que se acredita. Siempre usar campos snapshot para mostrar.
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Pago origen (nullable — NC puede ser standalone desde la factura).
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    /**
     * Reversión de asignación que disparó esta NC (nullable).
     */
    public function allocationReversal(): BelongsTo
    {
        return $this->belongsTo(CollectionAllocationReversal::class, 'collection_allocation_reversal_id');
    }

    /**
     * FK para navegación — siempre usar snapshot para mostrar.
     */
    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class, 'credit_note_id')->orderBy('sort_order');
    }

    /**
     * Aplicaciones del crédito de esta NC a otras facturas.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(CreditNoteApplication::class, 'credit_note_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    public function getPdfView(): string
    {
        return 'sales::pdf.v1.sri.credit-note';
    }

    public function getPdfFileType(): FileTypeEnum
    {
        return FileTypeEnum::CreditNotePdf;
    }

    public function getPdfEagerLoads(): array
    {
        return ['items.taxes', 'invoice:id,code,establishment_code,emission_point_code,sequential_number,issue_date', 'company', 'creator:id,name'];
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
        return 'sales::pdf.v2.ride-credit-note';
    }

    public function getRidePdfType(): string
    {
        return 'credit-note';
    }

    public function getRidePdfStoragePath(string $tenantRuc): string
    {
        $year = $this->issue_date?->year ?? now()->year;

        return "tenants/{$tenantRuc}/pdfs/v2/ride/credit-notes/{$year}/{$this->access_key}.pdf";
    }

    public function getRidePdfStorageDisk(): string
    {
        return 'local';
    }

    public function getRidePdfEagerLoads(): array
    {
        return ['items.taxes', 'invoice:id,code,establishment_code,emission_point_code,sequential_number,issue_date', 'company', 'creator:id,name'];
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
        return 'NOTA DE CRÉDITO';
    }

    /**
     * @return array<string, ApprovalFlow>
     */
    public function getApprovalFlows(): array
    {
        return [
            ApprovalFlowKey::CreditNoteIssuance->value => ApprovalFlow::make(ApprovalFlowKey::CreditNoteIssuance->value)
                ->step(
                    ApprovalStep::make('finance_director')
                        ->role(RoleEnum::FINANCE_DIRECTOR->value)
                        ->atLeast(1)
                ),
        ];
    }
}
