<?php

declare(strict_types=1);

namespace Modules\Workflow\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Company;
use Modules\People\Enums\RoleEnum;
use Modules\Workflow\Models\WorkflowApprovalSetting;

/**
 * Populates workflow_approval_settings for the DEMO tenant.
 * Identified by ruc = '0922895859001' to avoid hardcoding the numeric ID.
 *
 * Run: php artisan db:seed --class=Modules\\Workflow\\Database\\Seeders\\WorkflowApprovalSettingsSeeder
 */
final class WorkflowApprovalSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $demoCompany = Company::withoutGlobalScopes()
            ->where('ruc', '0922895859001')
            ->first();

        if ($demoCompany === null) {
            $this->command->warn('WorkflowApprovalSettingsSeeder: DEMO company not found. Skipping.');

            return;
        }

        $flows = [
            [
                'flow_key' => 'invoice_issuance',
                'is_enabled' => true,
                'min_amount' => 1000.00,
                'required_roles' => [RoleEnum::SALES_MANAGER->value],
            ],
            [
                'flow_key' => 'credit_note_issuance',
                'is_enabled' => true,
                'min_amount' => null,
                'required_roles' => [RoleEnum::FINANCE_DIRECTOR->value, RoleEnum::SALES_MANAGER->value],
            ],
            [
                'flow_key' => 'sales_order_confirmation',
                'is_enabled' => true,
                'min_amount' => 5000.00,
                'required_roles' => [RoleEnum::SALES_MANAGER->value],
            ],
            [
                'flow_key' => 'authorization',
                'is_enabled' => true,
                'min_amount' => 10000.00,
                'required_roles' => [RoleEnum::FINANCE_DIRECTOR->value],
            ],
        ];

        foreach ($flows as $flow) {
            WorkflowApprovalSetting::withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $demoCompany->id,
                    'flow_key' => $flow['flow_key'],
                ],
                array_merge($flow, ['company_id' => $demoCompany->id])
            );
        }

        $this->command->info("WorkflowApprovalSettingsSeeder: seeded {$demoCompany->trade_name} ({$demoCompany->ruc}) with ".count($flows).' approval flows.');
    }
}
