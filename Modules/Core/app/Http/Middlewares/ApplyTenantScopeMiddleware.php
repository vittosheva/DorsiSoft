<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middlewares;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

final class ApplyTenantScopeMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            setPermissionsTeamId((int) $tenant->getKey());

            $panelId = Filament::getCurrentPanel()?->getId() ?? 'default';

            $request->session()->put("filament.tenant.{$panelId}", $tenant->getKey());
        } else {
            setPermissionsTeamId(null);
        }

        return $next($request);
    }
}
