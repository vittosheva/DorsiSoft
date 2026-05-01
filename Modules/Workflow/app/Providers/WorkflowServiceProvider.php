<?php

declare(strict_types=1);

namespace Modules\Workflow\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Providers\Concerns\HandlesModuleConfiguration;
use Modules\Workflow\Approval\ApprovalRegistry;
use Modules\Workflow\Models\ApprovalFlow;
use Modules\Workflow\Models\ApprovalRecord;
use Modules\Workflow\Policies\ApprovalFlowPolicy;
use Modules\Workflow\Policies\ApprovalRecordPolicy;
use Nwidart\Modules\Traits\PathNamespace;

final class WorkflowServiceProvider extends ServiceProvider
{
    use HandlesModuleConfiguration;
    use PathNamespace;

    protected string $name = 'Workflow';

    protected string $nameLower = 'workflow';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->publishModuleConfig(dirname(__DIR__, 2));
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerPolicies();
        $this->registerApprovalFlows();
    }

    public function register(): void
    {
        $this->registerModuleConfig(dirname(__DIR__, 2), $this->nameLower);

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(ApprovalRegistry::class);
    }

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

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);
    }

    public function provides(): array
    {
        return [];
    }

    protected function registerCommands(): void {}

    protected function registerCommandSchedules(): void {}

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
        /* Gate::policy(ApprovalRecord::class, ApprovalRecordPolicy::class);
        Gate::policy(ApprovalFlow::class, ApprovalFlowPolicy::class); */
    }

    private function registerApprovalFlows(): void
    {
        // El módulo Workflow no registra flujos propios.
        // Cada módulo de negocio (Sales, Purchase, etc.) llama ApprovalRegistry::register()
        // en su propio ServiceProvider::boot().
    }
}
