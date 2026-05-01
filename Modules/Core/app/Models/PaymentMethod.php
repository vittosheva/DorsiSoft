<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;

final class PaymentMethod extends BaseModel
{
    use HasTenancy;
    use SoftDeletes;

    protected $table = 'core_payment_methods';

    protected $fillable = [
        'company_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
