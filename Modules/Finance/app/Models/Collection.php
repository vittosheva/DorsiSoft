<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Contracts\DocumentContract;
use Modules\Core\Models\Traits\HasDocumentBehavior;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Models\Traits\HasYearlyAutoCode;
use Modules\Core\Support\Models\BaseModel;
use Modules\Finance\Enums\CollectionMethodEnum;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\People\Enums\RoleEnum;
use Modules\People\Models\BusinessPartner;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\Invoice;
use Modules\Workflow\Approval\ApprovalFlow;
use Modules\Workflow\Approval\ApprovalStep;
use Modules\Workflow\Contracts\Approvable;
use Modules\Workflow\Traits\HasApprovals;

final class Collection extends BaseModel implements Approvable, DocumentContract
{
    use HasApprovals;
    use HasDocumentBehavior;
    use HasTenancy;
    use HasYearlyAutoCode;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sales_collections';

    protected $fillable = [
        'company_id',
        'code',
        'business_partner_id',
        'credit_note_id',
        'customer_name',
        'collection_date',
        'amount',
        'currency_code',
        'collection_method',
        'reference_number',
        'notes',
        'voided_at',
        'voided_reason',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'amount' => 'decimal:4',
            'collection_method' => CollectionMethodEnum::class,
            'voided_at' => 'datetime',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'COB';
    }

    public static function availableAmountSql(string $qualifiedTable = 'sales_collections'): string
    {
        return <<<SQL
            CASE
                WHEN {$qualifiedTable}.voided_at IS NULL THEN GREATEST(
                    {$qualifiedTable}.amount - COALESCE(
                        (
                            SELECT SUM(sales_collection_allocations.amount)
                            FROM sales_collection_allocations
                            WHERE sales_collection_allocations.collection_id = {$qualifiedTable}.id
                        ),
                        0
                    ),
                    0
                )
                ELSE 0
            END
        SQL;
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    /**
     * @return array<string, ApprovalFlow>
     */
    public function getApprovalFlows(): array
    {
        return [
            'authorization' => ApprovalFlow::make('authorization')
                ->step(
                    ApprovalStep::make('finance_director')
                        ->role(RoleEnum::FINANCE_DIRECTOR->value)
                ),
        ];
    }

    public function getAvailableAmountAttribute(): string
    {
        if ($this->isVoided()) {
            return '0.0000';
        }

        $allocatedAmount = CollectionAllocationMath::normalize($this->getAttribute('allocated_amount') ?? 0);

        return CollectionAllocationMath::pending($this->amount, $allocatedAmount);
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class, 'credit_note_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CollectionAllocation::class, 'collection_id');
    }

    public function allocationReversals(): HasMany
    {
        return $this->hasMany(CollectionAllocationReversal::class, 'collection_id');
    }

    public function invoices(): BelongsToMany
    {
        return $this
            ->belongsToMany(Invoice::class, 'sales_collection_allocations', 'collection_id', 'invoice_id')
            ->withPivot('amount', 'allocated_at')
            ->withTimestamps();
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->whereNull('voided_at');
    }
}
