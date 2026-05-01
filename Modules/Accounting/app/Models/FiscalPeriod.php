<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use DomainException;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Accounting\Enums\FiscalPeriodStatusEnum;
use Modules\Core\Models\Company;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;
use Modules\People\Models\User;

final class FiscalPeriod extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use Userstamps;

    protected $table = 'fin_fiscal_periods';

    protected $fillable = [
        'company_id',
        'year',
        'month',
        'name',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'closed_by_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
            'status' => FiscalPeriodStatusEnum::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    // Scopes
    #[Scope]
    public function current(Builder $query): Builder
    {
        $today = now();

        return $query
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    #[Scope]
    public function open(Builder $query): Builder
    {
        return $query->where('status', FiscalPeriodStatusEnum::OPEN);
    }

    #[Scope]
    public function closed(Builder $query): Builder
    {
        return $query->where('status', FiscalPeriodStatusEnum::CLOSED);
    }

    // Domain methods
    public function isOpen(): bool
    {
        return $this->status === FiscalPeriodStatusEnum::OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === FiscalPeriodStatusEnum::CLOSED;
    }

    public function close(User $user): void
    {
        if ($this->isClosed()) {
            throw new DomainException(__('The period is already closed.'));
        }

        $this->status = FiscalPeriodStatusEnum::CLOSED;
        $this->closed_at = now();
        $this->closed_by_id = $user->id;
        $this->save();
    }

    public function reopen(): void
    {
        if ($this->isOpen()) {
            throw new DomainException(__('The period is already open.'));
        }

        $this->status = FiscalPeriodStatusEnum::OPEN;
        $this->closed_at = null;
        $this->closed_by_id = null;
        $this->save();
    }
}
