<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class Parish extends Model
{
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_parishes';

    protected $fillable = [
        'city_id',
        'name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
