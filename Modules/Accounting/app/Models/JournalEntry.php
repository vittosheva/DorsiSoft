<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Accounting\Enums\JournalEntryStatusEnum;
use Modules\Core\Models\Company;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;
use Modules\People\Models\User;

final class JournalEntry extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'fin_journal_entries';

    protected $fillable = [
        'company_id',
        'fiscal_period_id',
        'reference',
        'description',
        'entry_date',
        'status',
        'source_type',
        'source_id',
        'total_debit',
        'total_credit',
        'approved_at',
        'approved_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'reversed_by_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => JournalEntryStatusEnum::class,
            'entry_date' => 'date',
            'approved_at' => 'datetime',
            'voided_at' => 'datetime',
            'total_debit' => 'decimal:4',
            'total_credit' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_entry_id')->orderBy('line_number');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function reversedByEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_entry_id');
    }

    /**
     * Origen polimórfico del asiento: Invoice, Collection, etc.
     */
    public function source(): MorphTo
    {
        return $this->morphTo('source');
    }

    // --- Domain Methods ---

    public function isDraft(): bool
    {
        return $this->status === JournalEntryStatusEnum::Draft;
    }

    public function isApproved(): bool
    {
        return $this->status === JournalEntryStatusEnum::Approved;
    }

    public function isVoided(): bool
    {
        return $this->status === JournalEntryStatusEnum::Voided;
    }

    /**
     * Verifica la igualdad debe = haber usando bcmath para evitar errores de punto flotante.
     */
    public function isBalanced(): bool
    {
        return bccomp(
            bcadd((string) $this->total_debit, '0', 4),
            bcadd((string) $this->total_credit, '0', 4),
            4
        ) === 0;
    }

    public function canBeApproved(): bool
    {
        return $this->isDraft() && $this->isBalanced() && $this->fiscalPeriod->isOpen();
    }

    public function canBeVoided(): bool
    {
        return $this->isApproved();
    }

    /**
     * Recalcula los totales sumando las líneas actuales con bcmath.
     */
    public function recalculateTotals(): void
    {
        $debit = '0';
        $credit = '0';

        foreach ($this->lines as $line) {
            $debit = bcadd($debit, (string) $line->debit, 4);
            $credit = bcadd($credit, (string) $line->credit, 4);
        }

        $this->total_debit = $debit;
        $this->total_credit = $credit;
    }

    // --- Scopes ---

    #[Scope]
    public function drafts(Builder $query): void
    {
        $query->where('status', JournalEntryStatusEnum::Draft->value);
    }

    #[Scope]
    public function approved(Builder $query): void
    {
        $query->where('status', JournalEntryStatusEnum::Approved->value);
    }

    #[Scope]
    public function forPeriod(Builder $query, int $fiscalPeriodId): void
    {
        $query->where('fiscal_period_id', $fiscalPeriodId);
    }

    #[Scope]
    public function fromSource(Builder $query, string $sourceType, int $sourceId): void
    {
        $query->where('source_type', $sourceType)->where('source_id', $sourceId);
    }

    protected static function booted(): void
    {
        self::creating(function (self $model) {
            if (! $model->reference) {
                $model->reference = self::generateReference($model->company_id);
            }
        });
    }

    private static function generateReference(int $companyId): string
    {
        $year = now()->year;
        $count = self::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('JE-%d-%06d', $year, $count + 1);
    }
}
