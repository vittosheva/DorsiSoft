<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\InventoryDocumentType;

final class InventoryDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'VENTA',
                'name' => 'Venta',
                'movement_type' => 'out',
                'affects_inventory' => true,
                'requires_source_document' => true,
                'is_active' => true,
            ],
            [
                'code' => 'COMPRA',
                'name' => 'Compra / Liquidación',
                'movement_type' => 'in',
                'affects_inventory' => true,
                'requires_source_document' => true,
                'is_active' => true,
            ],
            [
                'code' => 'TRANSFERENCIA',
                'name' => 'Transferencia entre bodegas',
                'movement_type' => 'transfer',
                'affects_inventory' => true,
                'requires_source_document' => false,
                'is_active' => true,
            ],
            [
                'code' => 'AJUSTE_ENTRADA',
                'name' => 'Ajuste de entrada',
                'movement_type' => 'in',
                'affects_inventory' => true,
                'requires_source_document' => false,
                'is_active' => true,
            ],
            [
                'code' => 'AJUSTE_SALIDA',
                'name' => 'Ajuste de salida',
                'movement_type' => 'out',
                'affects_inventory' => true,
                'requires_source_document' => false,
                'is_active' => true,
            ],
            [
                'code' => 'DEVOLUCION_VENTA',
                'name' => 'Devolución en venta (nota de crédito)',
                'movement_type' => 'in',
                'affects_inventory' => true,
                'requires_source_document' => true,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            InventoryDocumentType::updateOrCreate(
                ['code' => $type['code']],
                $type,
            );
        }
    }
}
