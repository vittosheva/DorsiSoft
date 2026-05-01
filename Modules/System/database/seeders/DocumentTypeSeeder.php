<?php

declare(strict_types=1);

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Company;
use Modules\System\Models\DocumentType;

final class DocumentTypeSeeder extends Seeder
{
    /**
     * Tipos de documento estándar para Ecuador (SRI).
     * Se ejecuta por empresa; si el tipo ya existe, lo actualiza.
     *
     * @var list<array<string, mixed>>
     */
    private array $types = [
        [
            'code' => 'invoice',
            'name' => 'Factura',
            'sri_code' => '01',
            'generates_receivable' => true,
            'generates_payable' => false,
            'affects_inventory' => true,
            'affects_accounting' => true,
            'requires_authorization' => true,
            'allows_credit' => true,
            'is_electronic' => true,
            'is_purchase' => false,
        ],
        [
            'code' => 'credit_note',
            'name' => 'Nota de Crédito',
            'sri_code' => '06',
            'generates_receivable' => false,
            'generates_payable' => false,
            'affects_inventory' => true,
            'affects_accounting' => true,
            'requires_authorization' => true,
            'allows_credit' => false,
            'is_electronic' => true,
            'is_purchase' => false,
        ],
        [
            'code' => 'debit_note',
            'name' => 'Nota de Débito',
            'sri_code' => '05',
            'generates_receivable' => true,
            'generates_payable' => false,
            'affects_inventory' => false,
            'affects_accounting' => true,
            'requires_authorization' => true,
            'allows_credit' => false,
            'is_electronic' => true,
            'is_purchase' => false,
        ],
        [
            'code' => 'purchase_settlement',
            'name' => 'Liquidación de Compra',
            'sri_code' => '04',
            'generates_receivable' => false,
            'generates_payable' => true,
            'affects_inventory' => true,
            'affects_accounting' => true,
            'requires_authorization' => true,
            'allows_credit' => false,
            'is_electronic' => true,
            'is_purchase' => true,
        ],
        [
            'code' => 'withholding',
            'name' => 'Comprobante de Retención',
            'sri_code' => '07',
            'generates_receivable' => false,
            'generates_payable' => true,
            'affects_inventory' => false,
            'affects_accounting' => true,
            'requires_authorization' => true,
            'allows_credit' => false,
            'is_electronic' => true,
            'is_purchase' => true,
        ],
        [
            'code' => 'delivery_guide',
            'name' => 'Guía de Remisión',
            'sri_code' => '06',
            'generates_receivable' => false,
            'generates_payable' => false,
            'affects_inventory' => false,
            'affects_accounting' => false,
            'requires_authorization' => true,
            'allows_credit' => false,
            'is_electronic' => true,
            'is_purchase' => false,
        ],
        [
            'code' => 'sales_order',
            'name' => 'Orden de Venta',
            'sri_code' => null,
            'generates_receivable' => false,
            'generates_payable' => false,
            'affects_inventory' => false,
            'affects_accounting' => false,
            'requires_authorization' => false,
            'allows_credit' => true,
            'is_electronic' => false,
            'is_purchase' => false,
        ],
    ];

    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            foreach ($this->types as $type) {
                DocumentType::withoutGlobalScopes()->updateOrCreate(
                    ['company_id' => $company->id, 'code' => $type['code']],
                    array_merge($type, ['is_active' => true]),
                );
            }
        });
    }
}
