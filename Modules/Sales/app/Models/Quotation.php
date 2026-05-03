<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Models\Traits\HasCustomerSnapshot;
use Modules\Core\Models\Traits\HasDocumentBehavior;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Models\Traits\HasYearlyAutoCode;
use Modules\Core\Support\Models\BaseModel;
use Modules\Finance\Models\PriceList;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Enums\DiscountTypeEnum;
use Modules\Sales\Enums\QuotationStatusEnum;
use Modules\Sales\Support\Pdf\CommercialDocumentPdfDataBuilder;

final class Quotation extends BaseModel implements GeneratesPdf
{
    use HasCustomerSnapshot;
    use HasDocumentBehavior;
    use HasFactory;
    use HasTenancy;
    use HasYearlyAutoCode;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sales_quotations';

    protected $fillable = [
        'company_id',
        'code',
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
        'price_list_id',
        'price_list_name',
        'currency_code',
        'status',
        'issue_date',
        'validity_days',
        'expires_at',
        'introduction',
        'notes',
        'discount_type',
        'discount_value',
        'subtotal',
        'tax_base',
        'discount_amount',
        'tax_amount',
        'total',
        'sent_at',
        'accepted_at',
        'order_id',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuotationStatusEnum::class,
            'discount_type' => DiscountTypeEnum::class,
            'issue_date' => 'date',
            'expires_at' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'validity_days' => 'integer',
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
        return 'COT';
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
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

    /**
     * FK para navegación — siempre usar price_list_name para mostrar.
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'quotation_id')->orderBy('sort_order');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    #[Scope]
    public function byStatus(Builder $query, QuotationStatusEnum $status): void
    {
        $query->where('status', $status);
    }

    #[Scope]
    public function expired(Builder $query): void
    {
        $query->where('expires_at', '<', now()->toDateString())
            ->whereNotIn('status', [QuotationStatusEnum::Accepted, QuotationStatusEnum::Rejected, QuotationStatusEnum::Expired]);
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->whereIn('status', [QuotationStatusEnum::Draft, QuotationStatusEnum::Sent]);
    }

    public function getPdfView(): string
    {
        return 'sales::pdf.v1.documents.quotation';
    }

    public function getPdfFileType(): FileTypeEnum
    {
        return FileTypeEnum::QuotationPdf;
    }

    public function getPdfEagerLoads(): array
    {
        return ['items.taxes', 'company:id,default_currency_id,logo_pdf_url,legal_name,ruc,phone,tax_address', 'seller:id,name', 'priceList:id,name'];
    }

    public function getPdfViewData(): array
    {
        return CommercialDocumentPdfDataBuilder::build($this);
    }

    public function getPdfDownloadFilename(): string
    {
        return str($this->code)->slug()->append('.pdf')->toString();
    }
}
