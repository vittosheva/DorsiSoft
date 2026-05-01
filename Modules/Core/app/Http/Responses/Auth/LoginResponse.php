<?php

declare(strict_types=1);

namespace Modules\Core\Http\Responses\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

final class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $panel = Filament::getCurrentPanel();
        $panelId = $panel->getId();

        if ($panelId === Filament::getPanel('system-admin')->getId()) {
            return redirect()->route('filament.system-admin.pages.dashboard');
        }

        $user = Filament::auth()->user();
        $companies = $user->companies()->select(['id', 'ruc', 'legal_name'])->get();

        if ($companies->isEmpty()) {
            return redirect()->to(Filament::getTenantRegistrationUrl() ?? route('filament.core-app.auth.login'));
        }

        if ($companies->count() === 1) {
            $company = $companies->first();
            session()->put("filament.tenant.{$panelId}", $company->getKey());
            session()->put('company_explicitly_selected', true);

            return redirect()->route('filament.core-app.pages.dashboard', ['tenant' => $company->ruc]);
        }

        return redirect()->route('filament.core-app.select-company');
    }
}
