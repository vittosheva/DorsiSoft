<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\Invoice;

final class CollectionAllocation extends BaseModel
{
    use HasTenancy;

    protected $table = 'sales_collection_allocations';

    protected $fillable = [
        'company_id',
        'collection_id',
        'credit_note_id',
        'origin_invoice_id',
        'invoice_id',
        'amount',
        'allocated_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'allocated_at' => 'datetime',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class, 'credit_note_id');
    }

    public function originInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'origin_invoice_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(CollectionAllocationReversal::class, 'collection_allocation_id');
    }
}
