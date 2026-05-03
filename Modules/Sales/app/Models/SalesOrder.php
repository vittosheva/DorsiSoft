<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
use Modules\People\Enums\RoleEnum;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Enums\DiscountTypeEnum;
use Modules\Sales\Enums\SalesOrderStatusEnum;
use Modules\Sales\Support\Pdf\CommercialDocumentPdfDataBuilder;
use Modules\Workflow\Approval\ApprovalFlow;
use Modules\Workflow\Approval\ApprovalStep;
use Modules\Workflow\Contracts\Approvable;
use Modules\Workflow\Enums\ApprovalFlowKey;
use Modules\Workflow\Traits\HasApprovals;

final class SalesOrder extends BaseModel implements Approvable, DocumentContract, GeneratesPdf
{
    use HasApprovals;
    use HasCustomerSnapshot;
    use HasDocumentBehavior;
    use HasFactory;
    use HasTenancy;
    use HasYearlyAutoCode;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sales_orders';

    protected $fillable = [
        'company_id',
        'code',
        'quotation_id',
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
        'notes',
        'discount_type',
        'discount_value',
        'subtotal',
        'tax_base',
        'discount_amount',
        'tax_amount',
        'total',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatusEnum::class,
            'discount_type' => DiscountTypeEnum::class,
            'issue_date' => 'date',
            'discount_value' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'tax_base' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'ORD';
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id')->withoutGlobalScopes();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'sales_order_id');
    }

    public function latestInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'sales_order_id')->latestOfMany();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'order_id')->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    #[Scope]
    public function byStatus(Builder $query, SalesOrderStatusEnum $status): void
    {
        $query->where('status', $status);
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->whereIn('status', [
            SalesOrderStatusEnum::Pending,
            SalesOrderStatusEnum::Confirmed,
            SalesOrderStatusEnum::PartiallyInvoiced,
            SalesOrderStatusEnum::FullyInvoiced,
        ]);
    }

    public function getPdfView(): string
    {
        return 'sales::pdf.v1.documents.sales-order';
    }

    public function getPdfFileType(): FileTypeEnum
    {
        return FileTypeEnum::SalesOrderPdf;
    }

    public function getPdfEagerLoads(): array
    {
        return ['items.taxes', 'company:id,default_currency_id,logo_pdf_url,legal_name,ruc,phone,tax_address', 'seller:id,name', 'quotation:id,code'];
    }

    public function getPdfViewData(): array
    {
        return CommercialDocumentPdfDataBuilder::build($this);
    }

    public function getPdfDownloadFilename(): string
    {
        return str($this->code)->slug()->append('.pdf')->toString();
    }

    /**
     * @return array<string, ApprovalFlow>
     */
    public function getApprovalFlows(): array
    {
        return [
            ApprovalFlowKey::SalesOrderConfirmation->value => ApprovalFlow::make(ApprovalFlowKey::SalesOrderConfirmation->value)
                ->step(
                    ApprovalStep::make('manager')
                        ->role(RoleEnum::SALES_MANAGER->value)
                        ->atLeast(1)
                ),
        ];
    }
}
