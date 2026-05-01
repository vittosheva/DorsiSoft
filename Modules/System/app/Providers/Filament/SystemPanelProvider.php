<?php

declare(strict_types=1);

namespace Modules\System\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\System\Filament\SystemAdmin\Pages\Auth\Login;
use Modules\System\Http\Middlewares\EnsureUserIsSuperAdmin;

final class SystemPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('system-admin')
            ->path('system')
            ->login(Login::class)
            ->authGuard('system')
            ->colors(['primary' => Color::Red])
            ->brandName(config('app.name').' — System')
            ->brandLogo(asset('img/dorsi-new.png'))
            ->darkModeBrandLogo(asset('img/dorsi-new-darkmode.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.ico'))
            ->font('Inter')
            ->defaultThemeMode(ThemeMode::Light)
            ->maxContentWidth(Width::Full)
            ->viteTheme([
                'Modules/Core/resources/css/filament/theme.css',
            ])
            ->topNavigation()
            ->globalSearch(false)
            ->strictAuthorization()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsSuperAdmin::class,
            ])
            ->discoverResources(
                in: $this->moduleAppPath('System', 'Filament/SystemAdmin/Resources'),
                for: $this->moduleNamespace('System', 'Filament\SystemAdmin\Resources'),
            )
            ->discoverPages(
                in: $this->moduleAppPath('System', 'Filament/SystemAdmin/Pages'),
                for: $this->moduleNamespace('System', 'Filament\SystemAdmin\Pages'),
            )
            ->navigationGroups([
                NavigationGroup::make(__('Tax Configuration'))->collapsed(false),
            ]);
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
