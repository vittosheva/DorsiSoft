<?php

declare(strict_types=1);

namespace Modules\Core\Providers\Filament;

use AzGasim\FilamentUnsavedChangesModal\FilamentUnsavedChangesModalPlugin;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
// use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Core\Filament\CoreApp\Pages\Auth\EditProfile;
use Modules\Core\Filament\CoreApp\Pages\Auth\Login;
use Modules\Core\Filament\CoreApp\Pages\CreateCompany;
use Modules\Core\Filament\CoreApp\Pages\EditCompany;
use Modules\Core\Http\Livewire\SelectCompanyPage;
use Modules\Core\Http\Middlewares\ApplyTenantScopeMiddleware;
use Modules\Core\Http\Middlewares\PanelCommonConfigMiddleware;
use Modules\Core\Http\Middlewares\RequireCompanySelectionMiddleware;
use Modules\Core\Models\Company;
use YousefAman\ModalRepeater\ModalRepeaterPlugin;

final class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('core-app')
            ->path('app')
            ->domain(config('app.url'))
            ->login(Login::class)
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->emailChangeVerification()
            ->profile(EditProfile::class, isSimple: false)
            ->topbar()
            ->topNavigation()
            // Sidebar
            ->sidebarWidth('17rem')
            ->sidebarFullyCollapsibleOnDesktop(fn () => ! $panel->hasTopNavigation())
            // Tenancy
            ->tenant(Company::class, 'ruc')
            ->tenantProfile(EditCompany::class)
            ->tenantRegistration(CreateCompany::class)
            ->tenantSwitcher(false)
            ->tenantMiddleware([
                RequireCompanySelectionMiddleware::class,
                ApplyTenantScopeMiddleware::class,
                PanelCommonConfigMiddleware::class,
            ], isPersistent: true)
            // Performance & Features
            ->spa(true, false)
            ->globalSearch(false)
            ->strictAuthorization()
            ->databaseNotifications()
            ->unsavedChangesAlerts(false)
            // UI
            ->brandName(config('app.name'))
            ->brandLogo(asset('img/dorsi-new.png'))
            ->darkModeBrandLogo(asset('img/dorsi-new-darkmode.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.ico'))
            ->font('Inter')
            ->colors([
                'primary' => [
                    50 => '#d8dee8',
                    100 => '#b2bed1',
                    200 => '#7f93b3',
                    300 => '#59739c',
                    400 => '#335386',
                    500 => '#193d77',
                    600 => '#00245d',
                    700 => '#002053',
                    800 => '#001c48',
                    900 => '#00183e',
                    950 => '#001434',
                ],
            ])
            ->defaultThemeMode(ThemeMode::Light)
            ->maxContentWidth(Width::Full)
            ->viteTheme([
                'Modules/Core/resources/css/filament/theme.css',
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                // VerifyCsrfToken::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentUnsavedChangesModalPlugin::make(),
                FilamentLanguageSwitcherPlugin::make()
                    ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE)
                    ->locales([
                        ['code' => 'en'],
                        ['code' => 'es'],
                    ])
                    ->showFlags(false),
                ModalRepeaterPlugin::make(),
            ])
            // Discovers
            ->discoverResources(in: $this->moduleAppPath('Core', 'Filament/CoreApp/Resources'), for: $this->moduleNamespace('Core', 'Filament\CoreApp\Resources'))
            ->discoverResources(in: $this->moduleAppPath('People', 'Filament/CoreApp/Resources'), for: $this->moduleNamespace('People', 'Filament\CoreApp\Resources'))
            ->discoverResources(in: $this->moduleAppPath('Finance', 'Filament/CoreApp/Resources'), for: $this->moduleNamespace('Finance', 'Filament\CoreApp\Resources'))
            ->discoverResources(in: $this->moduleAppPath('Inventory', 'Filament/CoreApp/Resources'), for: $this->moduleNamespace('Inventory', 'Filament\CoreApp\Resources'))
            ->discoverResources(in: $this->moduleAppPath('Sales', 'Filament/CoreApp/Resources'), for: $this->moduleNamespace('Sales', 'Filament\CoreApp\Resources'))
            ->discoverResources(in: $this->moduleAppPath('Sri', 'Filament/CoreApp/Resources'), for: $this->moduleNamespace('Sri', 'Filament\CoreApp\Resources'))
            ->discoverResources(in: $this->moduleAppPath('Reports', 'Filament/CoreApp/Resources'), for: $this->moduleNamespace('Reports', 'Filament\CoreApp\Resources'))
            ->discoverResources(in: $this->moduleAppPath('Accounting', 'Filament/CoreApp/Resources'), for: $this->moduleNamespace('Accounting', 'Filament\CoreApp\Resources'))
            ->discoverResources(in: $this->moduleAppPath('System', 'Filament/CoreApp/Resources'), for: $this->moduleNamespace('System', 'Filament\CoreApp\Resources'))
            ->discoverPages(in: $this->moduleAppPath('Core', 'Filament/CoreApp/Pages'), for: $this->moduleNamespace('Core', 'Filament\CoreApp\Pages'))
            ->discoverPages(in: $this->moduleAppPath('Reports', 'Filament/CoreApp/Pages'), for: $this->moduleNamespace('Reports', 'Filament\CoreApp\Pages'))
            ->discoverPages(in: $this->moduleAppPath('Inventory', 'Filament/CoreApp/Pages'), for: $this->moduleNamespace('Inventory', 'Filament\CoreApp\Pages'))
            ->discoverWidgets(in: $this->moduleAppPath('Core', 'Filament/CoreApp/Widgets'), for: $this->moduleNamespace('Core', 'Filament\CoreApp\Widgets'))
            ->discoverWidgets(in: $this->moduleAppPath('Reports', 'Filament/CoreApp/Widgets'), for: $this->moduleNamespace('Reports', 'Filament\CoreApp\Widgets'))
            ->discoverWidgets(in: $this->moduleAppPath('Workflow', 'Filament/CoreApp/Widgets'), for: $this->moduleNamespace('Workflow', 'Filament\CoreApp\Widgets'))
            ->discoverWidgets(in: $this->moduleAppPath('Workflow', 'Filament/Widgets'), for: $this->moduleNamespace('Workflow', 'Filament\Widgets'))
            ->discoverWidgets(in: $this->moduleAppPath('Sri', 'Filament/Widgets'), for: $this->moduleNamespace('Sri', 'Filament\Widgets'))
            ->discoverClusters(in: $this->moduleAppPath('Core', 'Filament/CoreApp/Clusters'), for: $this->moduleNamespace('Core', 'Filament\CoreApp\Clusters'))
            // ->pages([])
            // ->widgets([])
            ->navigationGroups([
                NavigationGroup::make(__('People'))->collapsed(),
                NavigationGroup::make(__('Sales'))->collapsed(),
                NavigationGroup::make(__('Inventory'))->collapsed(),
                NavigationGroup::make(__('Finance'))->collapsed(),
                NavigationGroup::make(__('Accounting'))->collapsed(),
                NavigationGroup::make(__('Reports'))->collapsed(),
                NavigationGroup::make(__('Settings'))->collapsed(),
            ])
            ->routes(function (): void {
                Route::get('select-company', SelectCompanyPage::class)
                    ->name('select-company')
                    ->middleware([Authenticate::class]);
            });
    }

    private function moduleAppPath(string $module, string $relativePath): string
    {
        return base_path('Modules/'.$module.'/app/'.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));
    }

    private function moduleNamespace(string $module, string $relativeNamespace): string
    {
        return 'Modules\\'.$module.'\\'.mb_trim($relativeNamespace, '\\');
    }
}
