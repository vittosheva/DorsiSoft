<?php

declare(strict_types=1);

namespace Modules\System\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Providers\Concerns\HandlesModuleConfiguration;
use Modules\System\Models\DocumentSeries;
use Modules\System\Models\DocumentType;
use Modules\System\Models\SriCatalog;
use Modules\System\Models\TaxCatalog;
use Modules\System\Models\TaxDefinition;
use Modules\System\Models\TaxRule;
use Modules\System\Models\TaxRuleLine;
use Modules\System\Models\TaxWithholdingRate;
use Modules\System\Policies\DocumentSeriesPolicy;
use Modules\System\Policies\DocumentTypePolicy;
use Modules\System\Policies\SriCatalogPolicy;
use Modules\System\Policies\TaxCatalogPolicy;
use Modules\System\Policies\TaxDefinitionPolicy;
use Modules\System\Policies\TaxRuleLinePolicy;
use Modules\System\Policies\TaxRulePolicy;
use Modules\System\Policies\TaxWithholdingRatePolicy;
use Nwidart\Modules\Traits\PathNamespace;

final class SystemServiceProvider extends ServiceProvider
{
    use HandlesModuleConfiguration;
    use PathNamespace;

    protected string $name = 'System';

    protected string $nameLower = 'system';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->publishModuleConfig(dirname(__DIR__, 2));
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerPolicies();
    }

    public function register(): void
    {
        $this->registerModuleConfig(dirname(__DIR__, 2), $this->nameLower);

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(Filament\SystemPanelProvider::class);
    }

    private function registerTranslations(): void
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

    private function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);
        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
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
        Gate::policy(DocumentType::class, DocumentTypePolicy::class);
        Gate::policy(DocumentSeries::class, DocumentSeriesPolicy::class);
        Gate::policy(SriCatalog::class, SriCatalogPolicy::class);
        Gate::policy(TaxCatalog::class, TaxCatalogPolicy::class);
        Gate::policy(TaxDefinition::class, TaxDefinitionPolicy::class);
        Gate::policy(TaxRule::class, TaxRulePolicy::class);
        Gate::policy(TaxRuleLine::class, TaxRuleLinePolicy::class);
        Gate::policy(TaxWithholdingRate::class, TaxWithholdingRatePolicy::class);
    }
}
