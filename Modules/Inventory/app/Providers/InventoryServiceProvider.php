<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Providers\Concerns\HandlesModuleConfiguration;
use Modules\Inventory\Console\Commands\RecalculateAbcClassification;
use Modules\Inventory\Interfaces\Contracts\StockReservationService;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Category;
use Modules\Inventory\Models\InventoryBalance;
use Modules\Inventory\Models\InventoryDocumentType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\SerialNumber;
use Modules\Inventory\Models\Unit;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Policies\BrandPolicy;
use Modules\Inventory\Policies\CategoryPolicy;
use Modules\Inventory\Policies\InventoryBalancePolicy;
use Modules\Inventory\Policies\InventoryDocumentTypePolicy;
use Modules\Inventory\Policies\InventoryMovementPolicy;
use Modules\Inventory\Policies\LotPolicy;
use Modules\Inventory\Policies\ProductPolicy;
use Modules\Inventory\Policies\SerialNumberPolicy;
use Modules\Inventory\Policies\UnitPolicy;
use Modules\Inventory\Policies\WarehousePolicy;
use Modules\Inventory\Services\BalanceMaterializer;
use Modules\Inventory\Services\DefaultStockReservationService;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Services\KardexService;
use Nwidart\Modules\Traits\PathNamespace;

final class InventoryServiceProvider extends ServiceProvider
{
    use HandlesModuleConfiguration;
    use PathNamespace;

    protected string $name = 'Inventory';

    protected string $nameLower = 'inventory';

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
        $this->app->bind(StockReservationService::class, DefaultStockReservationService::class);
        $this->app->singleton(BalanceMaterializer::class);
        $this->app->singleton(InventoryService::class);
        $this->app->singleton(KardexService::class);
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
            RecalculateAbcClassification::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('inventory:abc-recalculate')->monthly();
        });
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
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Unit::class, UnitPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(InventoryMovement::class, InventoryMovementPolicy::class);
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        Gate::policy(InventoryBalance::class, InventoryBalancePolicy::class);
        Gate::policy(InventoryDocumentType::class, InventoryDocumentTypePolicy::class);
        Gate::policy(Lot::class, LotPolicy::class);
        Gate::policy(SerialNumber::class, SerialNumberPolicy::class);
    }

    private function registerObservers(): void
    {
        // Quotation::observe(QuotationObserver::class);
    }
}
