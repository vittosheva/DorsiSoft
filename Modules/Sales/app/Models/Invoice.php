<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
use Modules\Core\Services\FileStoragePathService;
use Modules\Core\Support\Models\BaseModel;
use Modules\Finance\Models\Collection;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Models\PriceList;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Inventory\Models\Warehouse;
use Modules\People\Enums\RoleEnum;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Casts\MoneyAmount;
use Modules\Sales\Contracts\HasSriSequential;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Enums\DocumentTypeEnum;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Traits\AutoAssignsDocumentType;
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

final class Invoice extends BaseModel implements Approvable, DocumentContract, GeneratesPdf, GeneratesRidePdf, HasElectronicBilling, HasSriSequential
{
    use AutoAssignsDocumentType;
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

    protected $table = 'sales_invoices';

    protected $fillable = [
        'company_id',
        'code',
        'sales_order_id',
        'business_partner_id',
        'customer_name',
        'customer_trade_name',
        'customer_identification_type',
        'customer_identification',
        'customer_address',
        'customer_email',
        'customer_phone',
        'seller_id',
        'seller_name',
        'currency_code',
        'status',
        'issue_date',
        'due_date',
        'notes',
        'subtotal',
        'tax_base',
        'discount_amount',
        'tax_amount',
        'total',
        'paid_amount',
        'credited_amount',
        'establishment_code',
        'emission_point_code',
        'sequential_number',
        'access_key',
        'sri_payments',
        'additional_info',
        'voided_at',
        'voided_reason',
        'metadata',
        'electronic_status',
        'electronic_submitted_at',
        'electronic_authorized_at',
        'correction_status',
        'correction_source_id',
        'superseded_by_id',
        'correction_requested_at',
        'corrected_at',
        'correction_reason',
        'document_type_id',
        'warehouse_id',
        'price_list_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    private ?string $settlementSourceCache = null;

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatusEnum::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'voided_at' => 'datetime',
            'electronic_status' => ElectronicStatusEnum::class,
            'electronic_submitted_at' => 'datetime',
            'electronic_authorized_at' => 'datetime',
            'correction_status' => ElectronicCorrectionStatusEnum::class,
            'correction_requested_at' => 'datetime',
            'corrected_at' => 'datetime',
            'metadata' => 'array',
            'subtotal' => MoneyAmount::class,
            'tax_base' => MoneyAmount::class,
            'discount_amount' => MoneyAmount::class,
            'tax_amount' => MoneyAmount::class,
            'total' => MoneyAmount::class,
            'paid_amount' => MoneyAmount::class,
            'credited_amount' => MoneyAmount::class,
            'sri_payments' => 'array',
            'additional_info' => 'array',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'FAC';
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
        return DocumentTypeEnum::Invoice;
    }

    // HasElectronicBilling implementation

    public function getSriDocumentTypeCode(): string
    {
        return '01';
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
        return ['items.taxes', 'company'];
    }

    public function getXmlStoragePath(string $tenantRuc): string
    {
        return FileStoragePathService::getPath(
            FileTypeEnum::SriSignedXml,
            tenantId: $tenantRuc,
            context: [
                'document_type' => 'invoices',
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
                'document_type' => 'invoices',
                'year' => (string) ($this->issue_date?->year ?? now()->year),
                'filename' => "{$this->access_key}.xml",
            ],
        );
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    /**
     * FK para navegación — siempre usar snapshot (customer_name, etc.) para mostrar.
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    /**
     * FK para navegación — siempre usar snapshot (customer_name, etc.) para mostrar.
     */
    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    /**
     * FK para navegación — siempre usar seller_name para mostrar.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id')->withoutGlobalScopes();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id')->orderBy('sort_order');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CollectionAllocation::class, 'invoice_id');
    }

    public function collections(): BelongsToMany
    {
        return $this
            ->belongsToMany(Collection::class, 'sales_collection_allocations', 'invoice_id', 'collection_id')
            ->withPivot('amount', 'allocated_at')
            ->withTimestamps();
    }

    /**
     * Notas de débito emitidas contra esta factura.
     */
    public function debitNotes(): HasMany
    {
        return $this->hasMany(DebitNote::class, 'invoice_id');
    }

    /**
     * Notas de crédito emitidas contra esta factura.
     */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class, 'invoice_id');
    }

    /**
     * Aplicaciones de crédito de NCs externas que se asignaron a esta factura como destino.
     */
    public function creditNoteApplications(): HasMany
    {
        return $this->hasMany(CreditNoteApplication::class, 'invoice_id');
    }

    /**
     * Returns the payment state: 'unpaid', 'partially_paid', or 'paid'.
     * Considers both cash payments (paid_amount) and credit note credits (credited_amount).
     */
    public function paymentStatus(): string
    {
        $settled = bcadd(
            CollectionAllocationMath::normalize($this->paid_amount),
            CollectionAllocationMath::normalize($this->credited_amount),
            CollectionAllocationMath::SCALE
        );

        if (CollectionAllocationMath::isEffectivelyZero($settled)) {
            return 'unpaid';
        }

        if (CollectionAllocationMath::isPaid($settled, $this->total)) {
            return 'paid';
        }

        return 'partially_paid';
    }

    public function availableCreditableAmount(int $excludeCreditNoteId = 0): string
    {
        $alreadyCredited = CreditNote::withoutGlobalScopes()
            ->where('invoice_id', $this->getKey())
            ->whereNotIn('status', [CreditNoteStatusEnum::Draft->value, CreditNoteStatusEnum::Voided->value])
            ->where('electronic_status', ElectronicStatusEnum::Authorized->value)
            ->whereNull('deleted_at')
            ->when($excludeCreditNoteId > 0, fn (Builder $q) => $q->where('id', '!=', $excludeCreditNoteId))
            ->sum('total');

        return bcsub(
            CollectionAllocationMath::normalize($this->total),
            CollectionAllocationMath::normalize($alreadyCredited),
            CollectionAllocationMath::SCALE,
        );
    }

    public function settlementSource(): string
    {
        if ($this->settlementSourceCache !== null) {
            return $this->settlementSourceCache;
        }

        return $this->settlementSourceCache = $this->resolveSettlementSource();
    }

    public function settlementSourceLabel(): ?string
    {
        return match ($this->settlementSource()) {
            'credit_note' => __('Credit Note'),
            'collection' => __('Collection'),
            'mixed' => __('Mixed'),
            default => null,
        };
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    #[Scope]
    public function byStatus(Builder $query, InvoiceStatusEnum $status): void
    {
        $query->where('status', $status);
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->whereIn('status', [InvoiceStatusEnum::Draft, InvoiceStatusEnum::Issued]);
    }

    public function getPdfView(): string
    {
        return 'sales::pdf.v1.sri.invoice';
    }

    public function getPdfFileType(): FileTypeEnum
    {
        return FileTypeEnum::InvoicePdf;
    }

    public function getPdfEagerLoads(): array
    {
        return ['items.taxes', 'company', 'seller:id,name', 'salesOrder:id,code'];
    }

    public function getPdfViewData(): array
    {
        return CommercialDocumentPdfDataBuilder::build($this);
    }

    public function getPdfDownloadFilename(): string
    {
        $accessCode = $this->metadata['access_code'] ?? null;

        if ($accessCode) {
            return str($accessCode)->append('.pdf')->toString();
        }

        return str($this->code)->slug()->append('.pdf')->toString();
    }

    /**
     * @return array<string, ApprovalFlow>
     */
    public function getApprovalFlows(): array
    {
        return [
            ApprovalFlowKey::InvoiceIssuance->value => ApprovalFlow::make(ApprovalFlowKey::InvoiceIssuance->value)
                ->step(
                    ApprovalStep::make('manager')
                        ->role(RoleEnum::SALES_MANAGER->value)
                        ->atLeast(1)
                ),
        ];
    }

    // ─── GeneratesRidePdf ───────────────────────────────────────────────────

    public function getRidePdfView(): string
    {
        return 'sales::pdf.v2.ride-invoice';
    }

    public function getRidePdfType(): string
    {
        return 'invoice';
    }

    public function getRidePdfStoragePath(string $tenantRuc): string
    {
        return FileStoragePathService::getPath(
            FileTypeEnum::SriRidePdf,
            tenantId: $tenantRuc,
            context: [
                'document_type' => 'invoices',
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
        return ['items.taxes', 'company', 'creator:id,name'];
    }

    public function getRidePdfViewData(): array
    {
        return [];
    }

    public function getRidePdfDownloadFilename(): string
    {
        // return str($this->code)->slug()->append('-ride.pdf')->toString();
        $metadata = $this->metadata ?? [];
        $accessCode = $metadata['access_code'] ?? null;

        if ($accessCode) {
            return str($accessCode)->append('.pdf')->toString();
        }

        return str($this->code)->slug()->append('.pdf')->toString();
    }

    public function getRideSriDocumentTypeLabel(): string
    {
        return 'FACTURA';
    }

    protected function supportsInPlaceRejectedElectronicCorrection(): bool
    {
        return true;
    }

    protected function hasDownstreamElectronicCorrectionEffects(): bool
    {
        if (bccomp((string) ($this->paid_amount ?? '0.0000'), '0.0000', 4) !== 0) {
            return true;
        }

        if (bccomp((string) ($this->credited_amount ?? '0.0000'), '0.0000', 4) !== 0) {
            return true;
        }

        if (! $this->exists) {
            return false;
        }

        return $this->allocations()->exists()
            || $this->creditNotes()->exists()
            || $this->debitNotes()->exists()
            || $this->creditNoteApplications()->exists();
    }

    protected function customerEmail(): Attribute
    {
        return Attribute::make(
            set: function (mixed $value): ?string {
                if (blank($value)) {
                    return null;
                }

                if (is_array($value)) {
                    $value = collect($value)
                        ->first(static fn (mixed $email): bool => filled($email));
                }

                if (is_string($value)) {
                    $decoded = json_decode($value, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $value = collect($decoded)
                            ->first(static fn (mixed $email): bool => filled($email));
                    }
                }

                return filled($value) ? (string) $value : null;
            },
        );
    }

    private function resolveSettlementSource(): string
    {
        if (! $this->exists || ! $this->getKey()) {
            if (
                bccomp(CollectionAllocationMath::normalize($this->credited_amount), '0.0000', CollectionAllocationMath::SCALE) > 0
                && bccomp(CollectionAllocationMath::normalize($this->paid_amount), '0.0000', CollectionAllocationMath::SCALE) > 0
            ) {
                return 'mixed';
            }

            if (bccomp(CollectionAllocationMath::normalize($this->credited_amount), '0.0000', CollectionAllocationMath::SCALE) > 0) {
                return 'credit_note';
            }

            if (bccomp(CollectionAllocationMath::normalize($this->paid_amount), '0.0000', CollectionAllocationMath::SCALE) > 0) {
                return 'collection';
            }

            return 'none';
        }

        $creditNoteApplied = CollectionAllocation::query()
            ->where('invoice_id', $this->getKey())
            ->whereNotNull('credit_note_id')
            ->whereHas('collection', fn ($query) => $query->whereNull('voided_at'))
            ->sum('amount');

        $collectionApplied = CollectionAllocation::query()
            ->where('invoice_id', $this->getKey())
            ->whereNull('credit_note_id')
            ->whereHas('collection', fn ($query) => $query->whereNull('voided_at'))
            ->sum('amount');

        $hasCreditNoteApplied = bccomp(CollectionAllocationMath::normalize($creditNoteApplied), '0.0000', CollectionAllocationMath::SCALE) > 0;
        $hasCollectionApplied = bccomp(CollectionAllocationMath::normalize($collectionApplied), '0.0000', CollectionAllocationMath::SCALE) > 0;

        if ($hasCreditNoteApplied && $hasCollectionApplied) {
            return 'mixed';
        }

        if ($hasCreditNoteApplied) {
            return 'credit_note';
        }

        if ($hasCollectionApplied) {
            return 'collection';
        }

        // Legacy fallback for records that still carry historical values.
        if (bccomp(CollectionAllocationMath::normalize($this->credited_amount), '0.0000', CollectionAllocationMath::SCALE) > 0) {
            return 'credit_note';
        }

        if (bccomp(CollectionAllocationMath::normalize($this->paid_amount), '0.0000', CollectionAllocationMath::SCALE) > 0) {
            return 'collection';
        }

        return 'none';
    }
}
