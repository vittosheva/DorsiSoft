<?php

declare(strict_types=1);

namespace Modules\Core\Models\Scopes;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $tenant = Filament::getTenant();

        if ($tenant) {
            $builder->whereBelongsTo($tenant, 'company');
        }
    }
}
