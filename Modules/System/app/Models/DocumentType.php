<?php

declare(strict_types=1);

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Company;
use Modules\Core\Models\Traits\HasAutoCode;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;
use Modules\Sales\Enums\DocumentTypeEnum;

final class DocumentType extends BaseModel
{
    use HasAutoCode;
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sys_document_types';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'sri_code',
        'generates_receivable',
        'generates_payable',
        'affects_inventory',
        'affects_accounting',
        'requires_authorization',
        'allows_credit',
        'is_electronic',
        'is_purchase',
        'default_debit_account_code',
        'default_credit_account_code',
        'behavior_flags',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'generates_receivable' => 'boolean',
            'generates_payable' => 'boolean',
            'affects_inventory' => 'boolean',
            'affects_accounting' => 'boolean',
            'requires_authorization' => 'boolean',
            'allows_credit' => 'boolean',
            'is_electronic' => 'boolean',
            'is_purchase' => 'boolean',
            'is_active' => 'boolean',
            'behavior_flags' => 'array',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'DOCT';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function series(): HasMany
    {
        return $this->hasMany(DocumentSeries::class, 'document_type_id');
    }

    public function documentTypeEnum(): ?DocumentTypeEnum
    {
        return DocumentTypeEnum::tryFrom($this->code);
    }

    public function getBehaviorFlag(string $key, mixed $default = null): mixed
    {
        return data_get($this->behavior_flags, $key, $default);
    }

    public function hasBehaviorFlag(string $key): bool
    {
        return (bool) $this->getBehaviorFlag($key, false);
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    public function electronic(Builder $query): void
    {
        $query->where('is_electronic', true);
    }

    #[Scope]
    public function affectingAccounting(Builder $query): void
    {
        $query->where('affects_accounting', true);
    }
}
