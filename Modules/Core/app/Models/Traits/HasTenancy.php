<?php

declare(strict_types=1);

namespace Modules\Core\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\Company;
use Modules\Core\Models\Scopes\TenantScope;

trait HasTenancy
{
    public static function bootHasTenancy(): void
    {
        self::addGlobalScope(new TenantScope);

        self::creating(function (Model $model) {
            if (blank($model->company_id) && Auth::check() && filament()->getTenant()) {
                $model->company_id = filament()->getTenant()->id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
