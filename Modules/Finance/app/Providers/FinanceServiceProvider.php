<?php

declare(strict_types=1);

namespace Modules\Finance\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Providers\Concerns\HandlesModuleConfiguration;
use Modules\Finance\Console\Commands\ImportIceRatesCommand;
use Modules\Finance\Console\Commands\ImportIrBracketsCommand;
use Modules\Finance\Interfaces\Contracts\InvoicePoster;
use Modules\Finance\Models\Collection;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Models\CollectionAllocationReversal;
use Modules\Finance\Models\PriceList;
use Modules\Finance\Models\PriceListItem;
use Modules\Finance\Models\Tax;
use Modules\Finance\Models\TaxApplication;
use Modules\Finance\Observers\CollectionAllocationObserver;
use Modules\Finance\Observers\CollectionObserver;
use Modules\Finance\Observers\TaxRuleObserver;
use Modules\Finance\Policies\CollectionAllocationPolicy;
use Modules\Finance\Policies\CollectionAllocationReversalPolicy;
use Modules\Finance\Policies\CollectionPolicy;
use Modules\Finance\Policies\PriceListItemPolicy;
use Modules\Finance\Policies\PriceListPolicy;
use Modules\Finance\Policies\TaxApplicationPolicy;
use Modules\Finance\Policies\TaxPolicy;
use Modules\Finance\Services\DefaultInvoicePoster;
use Modules\Finance\Services\TaxRuleEngine;
use Modules\System\Models\TaxRule;
use Modules\Workflow\Approval\ApprovalRegistry;
use Nwidart\Modules\Traits\PathNamespace;

final class FinanceServiceProvider extends ServiceProvider
{
    use HandlesModuleConfiguration;
    use PathNamespace;

    protected string $name = 'Finance';

    protected string $nameLower = 'finance';

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
        $this->app->bind(InvoicePoster::class, DefaultInvoicePoster::class);
        $this->app->singleton(TaxRuleEngine::class);
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
            ImportIceRatesCommand::class,
            ImportIrBracketsCommand::class,
        ]);
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
        /* Gate::policy(Collection::class, CollectionPolicy::class);
        Gate::policy(CollectionAllocation::class, CollectionAllocationPolicy::class);
        Gate::policy(CollectionAllocationReversal::class, CollectionAllocationReversalPolicy::class);
        Gate::policy(PriceList::class, PriceListPolicy::class);
        Gate::policy(PriceListItem::class, PriceListItemPolicy::class);
        Gate::policy(Tax::class, TaxPolicy::class);
        Gate::policy(TaxApplication::class, TaxApplicationPolicy::class); */
    }

    private function registerObservers(): void
    {
        Collection::observe(CollectionObserver::class);
        CollectionAllocation::observe(CollectionAllocationObserver::class);
        TaxRule::observe(TaxRuleObserver::class);
    }

    private function registerApprovalFlows(): void
    {
        ApprovalRegistry::register('authorization', __('Collection Authorization'));
        ApprovalRegistry::registerModel('authorization', Collection::class);
    }
}
