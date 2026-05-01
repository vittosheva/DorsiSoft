<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class Currency extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_currencies';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function companiesUsingAsDefault(): HasMany
    {
        return $this->hasMany(Company::class, 'default_currency_id');
    }
}
