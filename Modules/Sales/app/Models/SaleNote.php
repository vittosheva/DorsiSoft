<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

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
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Enums\SaleNoteStatusEnum;
use Modules\Sales\Support\Pdf\CommercialDocumentPdfDataBuilder;

final class SaleNote extends BaseModel implements GeneratesPdf
{
    use HasCustomerSnapshot;
    use HasDocumentBehavior;
    use HasFactory;
    use HasTenancy;
    use HasYearlyAutoCode;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sales_sale_notes';

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
        'currency_code',
        'status',
        'issue_date',
        'notes',
        'subtotal',
        'tax_base',
        'discount_amount',
        'tax_amount',
        'total',
        'issued_at',
        'voided_at',
        'voided_reason',
        'metadata',
        'converted_to_invoice_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'price_list_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SaleNoteStatusEnum::class,
            'issue_date' => 'date',
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
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
        return 'NV';
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id')->withoutGlobalScopes();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleNoteItem::class, 'sale_note_id')->orderBy('sort_order');
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_to_invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    public function isConvertible(): bool
    {
        return $this->status === SaleNoteStatusEnum::Issued && ! $this->converted_to_invoice_id;
    }

    public function getPdfView(): string
    {
        return 'sales::pdf.v1.documents.sale-note';
    }

    public function getPdfFileType(): FileTypeEnum
    {
        return FileTypeEnum::InvoicePdf;
    }

    public function getPdfEagerLoads(): array
    {
        return ['items.taxes', 'company:id,default_currency_id,logo_pdf_url,legal_name,ruc,phone,tax_address', 'seller:id,name'];
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
