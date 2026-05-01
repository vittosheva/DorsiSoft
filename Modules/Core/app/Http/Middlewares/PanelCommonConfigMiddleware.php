<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middlewares;

use Closure;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Modules\Core\Filament\CoreApp\Pages\EditCompany;
use Modules\Sri\Enums\SriEnvironmentEnum;
use Symfony\Component\HttpFoundation\Response;

final class PanelCommonConfigMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            fn (): string => Blade::render('<meta name="description" content="{{ config(\'dorsi.long_description\') }}"><meta name="generator" content="Dorsi 1.0"><meta name="author" content="DorsiSoft">'),
        );

        $currentPanel = Filament::getCurrentPanel();
        $tenant = Filament::getTenant();

        if ($currentPanel->getId() === 'core-app') {
            FilamentView::registerRenderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('core::components.modal-draggable'),
            );

            FilamentView::registerRenderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@livewire(\'core.notification-bell-badge\')'),
            );

            FilamentView::registerRenderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('@vite(\'Modules/Core/resources/js/filament-panel.js\')'),
            );

            FilamentView::registerRenderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('core::components.footer'),
            );

            if (! $currentPanel->hasTopNavigation()) {
                FilamentView::registerRenderHook(
                    PanelsRenderHook::TENANT_MENU_AFTER,
                    fn () => view('core::components.navigation-filter', [
                        'isSidebarCollapsibleOnDesktop' => $currentPanel->isSidebarCollapsibleOnDesktop(),
                    ]),
                );
            }

            $sriEnvironment = $tenant?->sri_environment ?? SriEnvironmentEnum::TEST;

            if ($sriEnvironment === SriEnvironmentEnum::TEST) {
                FilamentView::registerRenderHook(
                    PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                    fn () => view('sri::components.sri-environment-indicator-header', [
                        'tenant' => $tenant,
                        'sriEnvironment' => $sriEnvironment,
                        'route' => EditCompany::getUrl(),
                    ]),
                );
            }
        }

        return $next($request);
    }
}
