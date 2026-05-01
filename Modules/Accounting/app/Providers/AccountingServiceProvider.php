<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Policies\ChartOfAccountPolicy;
use Modules\Accounting\Policies\FiscalPeriodPolicy;
use Modules\Accounting\Policies\JournalEntryPolicy;
use Modules\Accounting\Policies\JournalLinePolicy;
use Modules\Accounting\Services\AccountBalanceService;
use Modules\Accounting\Services\JournalEntryFactory;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Accounting\Services\JournalReferenceGenerator;
use Modules\Core\Providers\Concerns\HandlesModuleConfiguration;
use Nwidart\Modules\Traits\PathNamespace;

final class AccountingServiceProvider extends ServiceProvider
{
    use HandlesModuleConfiguration;
    use PathNamespace;

    protected string $name = 'Accounting';

    protected string $nameLower = 'accounting';

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
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerModuleConfig(dirname(__DIR__, 2), $this->nameLower);

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(JournalReferenceGenerator::class);
        $this->app->singleton(AccountBalanceService::class);
        $this->app->singleton(JournalEntryService::class);
        $this->app->singleton(JournalEntryFactory::class);
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
        Gate::policy(FiscalPeriod::class, FiscalPeriodPolicy::class);
        Gate::policy(ChartOfAccount::class, ChartOfAccountPolicy::class);
        Gate::policy(JournalEntry::class, JournalEntryPolicy::class);
        Gate::policy(JournalLine::class, JournalLinePolicy::class);
    }

    private function registerObservers(): void
    {
        //
    }
}
