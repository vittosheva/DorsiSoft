<?php

declare(strict_types=1);

namespace Modules\Workflow\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Company;
use Modules\People\Enums\RoleEnum;
use Modules\Workflow\Models\ApprovalFlow;
use Modules\Workflow\Models\ApprovalFlowRole;
use Modules\Workflow\Models\DocumentType;
use Spatie\Permission\Models\Role;

final class ApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        $demoCompany = Company::withoutGlobalScopes()
            ->where('ruc', '0922895859001')
            ->first();

        if ($demoCompany === null) {
            $this->command->warn('ApprovalFlowSeeder: DEMO company not found. Skipping.');

            return;
        }

        /** @var array<string, int> $typeIds */
        $typeIds = DocumentType::query()
            ->pluck('id', 'code')
            ->all();

        $flows = [
            // --- Sales Flows ---
            // key must match getApprovalFlows() keys in Invoice, CreditNote, SalesOrder models
            // and WorkflowApprovalSetting.flow_key values used by HasApprovals::approvalDecision()
            [
                'key' => 'invoice_issuance',
                'name' => 'Emisión de Factura',
                'document_type_code' => 'sales',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::SALES_MANAGER->value, 'step' => 1, 'required' => true],
                ],
            ],
            [
                'key' => 'sales_order_confirmation',
                'name' => 'Confirmación de Orden de Venta',
                'document_type_code' => 'sales',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::SALES_MANAGER->value, 'step' => 1, 'required' => true],
                ],
            ],
            [
                'key' => 'credit_note_issuance',
                'name' => 'Aprobación de Nota de Crédito',
                'document_type_code' => 'sales',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::SALES_MANAGER->value, 'step' => 1, 'required' => true],
                ],
            ],
            [
                'key' => 'refund_approval',
                'name' => 'Aprobación de Reembolso',
                'document_type_code' => 'sales',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::FINANCE_DIRECTOR->value, 'step' => 1, 'required' => true],
                ],
            ],
            // --- Purchase Flows ---
            [
                'key' => 'purchase_issuance',
                'name' => 'Emisión de Compra',
                'document_type_code' => 'purchase',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::PURCHASING_AGENT->value, 'step' => 1, 'required' => true],
                ],
            ],
            [
                'key' => 'purchase_approval',
                'name' => 'Aprobación de Compra',
                'document_type_code' => 'purchase',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::PURCHASING_AGENT->value, 'step' => 1, 'required' => true],
                ],
            ],
            [
                'key' => 'expense_approval',
                'name' => 'Aprobación de Gasto',
                'document_type_code' => 'purchase',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::FINANCE_DIRECTOR->value, 'step' => 1, 'required' => true],
                ],
            ],
            // --- Finance Flows ---
            [
                'key' => 'authorization',
                'name' => 'Autorización de Pago',
                'document_type_code' => 'finance',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::FINANCE_DIRECTOR->value, 'step' => 1, 'required' => true],
                ],
            ],
            [
                'key' => 'expense_reimbursement',
                'name' => 'Reembolso de Gastos',
                'document_type_code' => 'finance',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::FINANCE_DIRECTOR->value, 'step' => 1, 'required' => true],
                ],
            ],
            // --- Inventory Flows ---
            [
                'key' => 'inventory_adjustment',
                'name' => 'Ajuste de Inventario',
                'document_type_code' => 'inventory',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::INVENTORY_MANAGER->value, 'step' => 1, 'required' => true],
                ],
            ],
            [
                'key' => 'stock_transfer',
                'name' => 'Transferencia de Stock',
                'document_type_code' => 'inventory',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::INVENTORY_MANAGER->value, 'step' => 1, 'required' => true],
                ],
            ],
            // --- SRI Document Flows ---
            [
                'key' => 'withholding_release',
                'name' => 'Liberación de Retención',
                'document_type_code' => 'finance',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::ACCOUNTANT->value, 'step' => 1, 'required' => true],
                ],
            ],
            [
                'key' => 'settlement_approval',
                'name' => 'Aprobación de Liquidación de Compra',
                'document_type_code' => 'purchase',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::FINANCE_DIRECTOR->value, 'step' => 1, 'required' => true],
                ],
            ],
            // --- General/Other Flows ---
            [
                'key' => 'general_approval',
                'name' => 'Aprobación de Documento General',
                'document_type_code' => 'general',
                'is_active' => true,
                'min_amount' => 0.00,
                'roles' => [
                    ['name' => RoleEnum::ADMIN->value, 'step' => 1, 'required' => true],
                ],
            ],
        ];

        foreach ($flows as $flowData) {
            $documentTypeId = $typeIds[$flowData['document_type_code']] ?? null;

            if ($documentTypeId === null) {
                $this->command->warn("ApprovalFlowSeeder: DocumentType '{$flowData['document_type_code']}' not found. Skipping '{$flowData['name']}'.");

                continue;
            }

            $flow = ApprovalFlow::updateOrCreate([
                'company_id' => $demoCompany->id,
                'key' => $flowData['key'],
            ], [
                'company_id' => $demoCompany->id,
                'key' => $flowData['key'],
                'name' => $flowData['name'],
                'document_type_id' => $documentTypeId,
                'is_active' => $flowData['is_active'],
                'min_amount' => $flowData['min_amount'],
            ]);

            foreach ($flowData['roles'] as $roleData) {
                $role = Role::where('name', $roleData['name'])
                    ->where('company_id', $demoCompany->id)
                    ->first();
                if (! $role) {
                    $this->command->warn("ApprovalFlowSeeder: Role '{$roleData['name']}' for company_id {$demoCompany->id} not found. Skipping.");

                    continue;
                }
                ApprovalFlowRole::updateOrCreate([
                    'approval_flow_id' => $flow->id,
                    'role_id' => $role->id,
                    'step' => $roleData['step'],
                ], [
                    'required' => $roleData['required'],
                ]);
            }
        }

        $this->command->info("ApprovalFlowSeeder: seeded {$demoCompany->trade_name} ({$demoCompany->ruc}) with ".count($flows).' approval flows.');
    }
}
