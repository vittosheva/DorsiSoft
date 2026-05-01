<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Support\Pdf\PdfDateFormatter;
use Modules\People\Models\User;
use Modules\Sales\Models\Invoice;

final class CollectionAllocationReversal extends Model implements GeneratesPdf
{
    use HasFactory;

    public const TYPE_FULL = 'full';

    public const TYPE_PARTIAL = 'partial';

    protected $table = 'sales_collection_allocation_reversals';

    protected $fillable = [
        'collection_id',
        'collection_allocation_id',
        'invoice_id',
        'reversed_amount',
        'reversal_type',
        'reason',
        'reversed_at',
        'reversed_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'reversed_amount' => 'decimal:4',
            'reversed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(CollectionAllocation::class, 'collection_allocation_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function getCompanyIdAttribute(): ?int
    {
        return $this->collection?->company_id;
    }

    public function getCodeAttribute(): string
    {
        return 'REV-'.mb_str_pad((string) $this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    public function getCompanyAttribute(): mixed
    {
        return $this->collection?->company;
    }

    public function getPdfView(): string
    {
        return 'sales::pdf.v1.documents.collection-reversal-receipt';
    }

    public function getPdfFileType(): FileTypeEnum
    {
        return FileTypeEnum::CollectionAllocationReversalPdf;
    }

    public function getPdfEagerLoads(): array
    {
        return ['collection.company', 'invoice', 'reversedBy:id,name'];
    }

    public function getPdfViewData(): array
    {
        return [
            'formattedCollectionDate' => PdfDateFormatter::formatDate($this->collection?->collection_date),
            'formattedInvoiceDate' => PdfDateFormatter::formatDate($this->invoice?->issue_date),
            'formattedReversedAt' => PdfDateFormatter::formatDateTime($this->reversed_at),
            'formattedGeneratedAt' => PdfDateFormatter::formatDateTime(now()),
        ];
    }

    public function getPdfDownloadFilename(): string
    {
        return 'collection-reversal-receipt-'.$this->getKey().'.pdf';
    }
}
