<?php

declare(strict_types=1);

namespace Modules\Sri\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\Establishment;
use Modules\Core\Providers\Concerns\HandlesModuleConfiguration;
use Modules\Sri\Console\Commands\ImportAtsCodesCommand;
use Modules\Sri\Contracts\SriAuthorizationServiceContract;
use Modules\Sri\Contracts\SriReceptionServiceContract;
use Modules\Sri\Jobs\PollSriAuthorization;
use Modules\Sri\Models\DocumentSequence;
use Modules\Sri\Observers\EmissionPointDocumentSequenceObserver;
use Modules\Sri\Observers\EstablishmentDocumentSequenceObserver;
use Modules\Sri\Policies\DocumentSequencePolicy;
use Modules\Sri\Services\Soap\SriAuthorizationService;
use Modules\Sri\Services\Soap\SriReceptionService;
use Modules\Sri\Services\Sri\Contracts\SriServiceInterface;
use Modules\Sri\Services\Sri\SriService;
use Nwidart\Modules\Traits\PathNamespace;

final class SriServiceProvider extends ServiceProvider
{
    use HandlesModuleConfiguration;
    use PathNamespace;

    protected string $name = 'Sri';

    protected string $nameLower = 'sri';

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
        $this->registerObservers();
        $this->registerApprovalFlows();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerModuleConfig(dirname(__DIR__, 2), $this->nameLower);

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(SriServiceInterface::class, SriService::class);
        $this->app->singleton(SriReceptionServiceContract::class, SriReceptionService::class);
        $this->app->singleton(SriAuthorizationServiceContract::class, SriAuthorizationService::class);

        $this->registerPolicies();
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

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
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
        $this->commands([
            ImportAtsCodesCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->job(new PollSriAuthorization, 'electronic-billing')
                ->everyFiveMinutes()
                ->withoutOverlapping();
        });
    }

    private function registerPolicies(): void
    {
        // Gate::policy(DocumentSequence::class, DocumentSequencePolicy::class);
    }

    private function registerObservers(): void
    {
        Establishment::observe(EstablishmentDocumentSequenceObserver::class);
        EmissionPoint::observe(EmissionPointDocumentSequenceObserver::class);
    }

    /**
     * Register SRI document approval flow model mappings with the central registry.
     */
    private function registerApprovalFlows(): void
    {
        // No SRI-owned approval models are registered here.
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
}
