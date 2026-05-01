<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Modules\Core\Http\Livewire\SelectCompanyPage;
use Modules\Core\Http\Responses\Auth\LoginResponse;
use Modules\Core\Livewire\NotificationBellBadge;
use Modules\Core\Models\Company;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\Establishment;
use Modules\Core\Policies\CompanyPolicy;
use Modules\Core\Policies\EmissionPointPolicy;
use Modules\Core\Policies\EstablishmentPolicy;
use Modules\Core\Providers\Concerns\HandlesModuleConfiguration;
use Nwidart\Modules\Traits\PathNamespace;

final class CoreServiceProvider extends ServiceProvider
{
    use HandlesModuleConfiguration;
    use PathNamespace;

    protected string $name = 'Core';

    protected string $nameLower = 'core';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->publishModuleConfig(dirname(__DIR__, 2));
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerPolicies();
        $this->registerObservers();

        Livewire::component('core.select-company-page', SelectCompanyPage::class);
        Livewire::component('core.notification-bell-badge', NotificationBellBadge::class);

        Password::defaults(function (): ?Password {
            return Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Prevención de lazy loading para detectar N+1 solo en desarrollo
        if (app()->environment('local', 'development')) {
            Model::preventLazyLoading();
        }

        $this->registerModuleConfig(dirname(__DIR__, 2), $this->nameLower, [
            'dorsi.php' => 'dorsi',
        ]);

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace('Modules\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    private function registerPolicies(): void
    {
        /* Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Establishment::class, EstablishmentPolicy::class);
        Gate::policy(EmissionPoint::class, EmissionPointPolicy::class); */
    }

    private function registerObservers(): void
    {
        // Quotation::observe(QuotationObserver::class);
    }
}
