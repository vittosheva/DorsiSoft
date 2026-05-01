<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Providers\Concerns\HandlesModuleConfiguration;
use Modules\Finance\Interfaces\Contracts\InvoicePoster;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\CreditNoteApplication;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\DeliveryGuide;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\TaxWithholdingRule;
use Modules\Sales\Models\Withholding;
use Modules\Sales\Observers\CreditNoteObserver;
use Modules\Sales\Observers\DebitNoteObserver;
use Modules\Sales\Observers\InvoiceObserver;
use Modules\Sales\Observers\PurchaseSettlementObserver;
use Modules\Sales\Observers\QuotationObserver;
use Modules\Sales\Observers\SalesOrderObserver;
use Modules\Sales\Observers\WithholdingObserver;
use Modules\Sales\Policies\CreditNoteApplicationPolicy;
use Modules\Sales\Policies\CreditNotePolicy;
use Modules\Sales\Policies\DebitNotePolicy;
use Modules\Sales\Policies\DeliveryGuidePolicy;
use Modules\Sales\Policies\InvoicePolicy;
use Modules\Sales\Policies\PurchaseSettlementPolicy;
use Modules\Sales\Policies\QuotationPolicy;
use Modules\Sales\Policies\SalesOrderPolicy;
use Modules\Sales\Policies\TaxWithholdingRulePolicy;
use Modules\Sales\Policies\WithholdingPolicy;
use Modules\Sales\Services\SalesInvoicePoster;
use Modules\Sales\Services\WithholdingAccountingService;
use Modules\Sales\Services\WithholdingCalculationService;
use Modules\Sales\Services\WithholdingSuggestionService;
use Modules\Workflow\Approval\ApprovalRegistry;
use Nwidart\Modules\Traits\PathNamespace;

final class SalesServiceProvider extends ServiceProvider
{
    use HandlesModuleConfiguration;
    use PathNamespace;

    protected string $name = 'Sales';

    protected string $nameLower = 'sales';

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

        // Override Finance's DefaultInvoicePoster with the Sales implementation
        $this->app->bind(InvoicePoster::class, SalesInvoicePoster::class);

        $this->app->singleton(WithholdingCalculationService::class);
        $this->app->singleton(WithholdingSuggestionService::class);
        $this->app->singleton(WithholdingAccountingService::class);
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
        Gate::policy(CreditNote::class, CreditNotePolicy::class);
        Gate::policy(CreditNoteApplication::class, CreditNoteApplicationPolicy::class);
        Gate::policy(DebitNote::class, DebitNotePolicy::class);
        Gate::policy(DeliveryGuide::class, DeliveryGuidePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(PurchaseSettlement::class, PurchaseSettlementPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
        Gate::policy(Withholding::class, WithholdingPolicy::class);
        // Policy for tax withholding rules (UI + management)
        Gate::policy(TaxWithholdingRule::class, TaxWithholdingRulePolicy::class);
    }

    private function registerObservers(): void
    {
        CreditNote::observe(CreditNoteObserver::class);
        DebitNote::observe(DebitNoteObserver::class);
        Invoice::observe(InvoiceObserver::class);
        PurchaseSettlement::observe(PurchaseSettlementObserver::class);
        Quotation::observe(QuotationObserver::class);
        SalesOrder::observe(SalesOrderObserver::class);
        Withholding::observe(WithholdingObserver::class);
    }

    /**
     * Register Sales module approval flow categories with the central registry.
     * Each category maps to the document flow types handled by this module.
     */
    private function registerApprovalFlows(): void
    {
        ApprovalRegistry::register('issuance', __('Document Issuance (Invoice / Credit Note)'));
        ApprovalRegistry::register('confirmation', __('Sales Order Confirmation'));

        // Map specific flow keys to their Eloquent model classes for the pending approvals widget.
        ApprovalRegistry::registerModel('invoice_issuance', Invoice::class);
        ApprovalRegistry::registerModel('credit_note_issuance', CreditNote::class);
        ApprovalRegistry::registerModel('sales_order_confirmation', SalesOrder::class);
        ApprovalRegistry::registerModel('withholding_release', Withholding::class);
        ApprovalRegistry::registerModel('settlement_approval', PurchaseSettlement::class);
    }
}
