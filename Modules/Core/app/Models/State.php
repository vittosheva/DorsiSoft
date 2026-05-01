<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class State extends Model
{
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_states';

    protected $fillable = [
        'country_id',
        'name',
        'iso2',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
