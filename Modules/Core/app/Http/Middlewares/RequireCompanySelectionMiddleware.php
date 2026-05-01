<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;

final class RequireCompanySelectionMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->header('X-Livewire')) {
            return $next($request);
        }

        if (! session()->has('company_explicitly_selected')) {
            return redirect()->route('filament.core-app.select-company');
        }

        return $next($request);
    }
}
