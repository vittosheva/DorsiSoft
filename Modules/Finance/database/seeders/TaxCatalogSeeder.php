<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\System\Enums\TaxGroupEnum;
use Modules\System\Models\TaxCatalog;

final class TaxCatalogSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $catalogs = [
            [
                'code' => 'IVA',
                'name' => 'Impuesto al Valor Agregado',
                'tax_group' => TaxGroupEnum::Iva->value,
                'description' => 'Impuesto al Valor Agregado — tarifa general y tarifas especiales SRI Ecuador',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'ICE',
                'name' => 'Impuesto a los Consumos Especiales',
                'tax_group' => TaxGroupEnum::Ice->value,
                'description' => 'Impuesto a los Consumos Especiales — bienes y servicios gravados según Ley de Régimen Tributario Interno',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'IR',
                'name' => 'Impuesto a la Renta',
                'tax_group' => TaxGroupEnum::Renta->value,
                'description' => 'Impuesto a la Renta — personas naturales, sociedades y RIMPE',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'ISD',
                'name' => 'Impuesto a la Salida de Divisas',
                'tax_group' => TaxGroupEnum::Isd->value,
                'description' => 'Impuesto a la Salida de Divisas — transferencias al exterior',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($catalogs as $data) {
            TaxCatalog::updateOrCreate(['code' => $data['code']], $data);
        }

        $this->reportCreated(count($catalogs));
    }
}
