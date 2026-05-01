<?php

declare(strict_types=1);

namespace Modules\Workflow\Approval;

use Modules\Workflow\Models\ApprovalFlow;

final class ApprovalRegistry
{
    /**
     * Module-registered flow categories: keyed by category slug, value is a human label.
     * Populated via ApprovalRegistry::register() calls in each module's ServiceProvider.
     *
     * @var array<string, string>
     */
    private static array $registrations = [];

    /**
     * Maps each specific flow key to its Eloquent model class.
     * Used by PendingApprovalsWidget and other consumers that need to query approvable models.
     * Populated via ApprovalRegistry::registerModel() calls in each module's ServiceProvider.
     *
     * @var array<string, class-string>
     */
    private static array $flowModels = [];

    /**
     * Register a flow category from a module's ServiceProvider.
     * This provides a code-first registry of known flow types that can be used
     * for seeding, UI hints, and validation without scanning model files.
     *
     * @example ApprovalRegistry::register('issuance', 'Invoice / Credit Note Issuance')
     */
    public static function register(string $category, string $label): void
    {
        self::$registrations[$category] = $label;
    }

    /**
     * Returns all code-registered flow categories.
     *
     * @return array<string, string>
     */
    public static function registrations(): array
    {
        return self::$registrations;
    }

    /**
     * Register the Eloquent model class that owns a specific flow key.
     * This enables the PendingApprovalsWidget to query pending documents per flow.
     *
     * @param  class-string  $modelClass
     *
     * @example ApprovalRegistry::registerModel('invoice_issuance', Invoice::class)
     */
    public static function registerModel(string $flowKey, string $modelClass): void
    {
        self::$flowModels[$flowKey] = $modelClass;
    }

    /**
     * Returns the flow key → model class map.
     *
     * @return array<string, class-string>
     */
    public static function flowModels(): array
    {
        return self::$flowModels;
    }

    /**
     * Returns all active approval flows as grouped options for Filament's Select component.
     * The option value is the string `key` field (e.g. 'invoice_issuance'), which is what
     * WorkflowApprovalSetting.flow_key stores and HasApprovals::approvalDecision() looks up.
     *
     * @param  int|null  $companyId  Optionally filter by company
     * @return array<string, array<string, string>>
     */
    public function all(?int $companyId = null): array
    {
        $query = ApprovalFlow::query()
            ->with('documentType:id,name')
            ->select(['id', 'key', 'name', 'document_type_id'])
            ->where('is_active', true);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $flows = $query->get();

        $grouped = [];
        foreach ($flows as $flow) {
            $group = __($flow->documentType?->name ?? 'General');
            $grouped[$group][$flow->key] = $flow->name;
        }
        ksort($grouped);

        return $grouped;
    }

    /**
     * Returns a flat key=>name array of all active approval flows.
     *
     * @return array<string, string>
     */
    public function flat(?int $companyId = null): array
    {
        $query = ApprovalFlow::query()->where('is_active', true);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->pluck('name', 'key')->all();
    }

    public function getLabel(string $key, ?int $companyId = null): ?string
    {
        $query = ApprovalFlow::query()->where('key', $key);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->value('name');
    }
}
